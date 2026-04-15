<?php

namespace App\Http\Controllers;

use App\Models\BiosecurityLog;
use Illuminate\Http\Request;

class BiosecurityLogController extends Controller
{
    /**
     * Get all biosecurity logs grouped by type
     */
    public function index()
    {
        $logs = BiosecurityLog::orderBy('created_at', 'desc')->get();

        // Group logs by type
        $groupedLogs = [];
        foreach ($logs as $log) {
            $type = $log->type ?? 'Cleaning';
            if (!isset($groupedLogs[$type])) {
                $groupedLogs[$type] = [];
            }
            $groupedLogs[$type][] = $this->formatLogForDisplay($log);
        }

        // Get overview stats
        $overview = $this->getOverview();

        return response()->json([
            'overview' => $overview,
            'logs' => $groupedLogs,
        ]);
    }

    /**
     * Store a new biosecurity log
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'house' => 'nullable|string|max:50',
            'pen' => 'nullable|string|max:50',
            'activity' => 'nullable|string|max:255',
            'disinfectant_used' => 'nullable|string|max:255',
            'performed_by' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:100',
            'role' => 'nullable|string|max:100',
            'date' => 'nullable|date',
            'time' => 'nullable',
            'foot_bath' => 'nullable|string|max:10',
            'boots_changed' => 'nullable|string|max:10',
            'protective_clothing' => 'nullable|string|max:10',
            'time_in' => 'nullable',
            'time_out' => 'nullable',
            'purpose' => 'nullable|string|max:255',
            'sanitation' => 'nullable|string|max:10',
            'ppe' => 'nullable|string|max:10',
            'monitored_by' => 'nullable|string|max:100',
            'batch' => 'nullable|string|max:50',
            'flocks_with_cases' => 'nullable|string|max:50',
            'age' => 'nullable|string|max:50',
            'average_weight' => 'nullable|string|max:50',
            'target' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        // Convert IDs to display values for Cleaning, Personnel Biosecurity Logs, Visitors, Personnel Entry Logs, and Weight Sampling
        $type = $validated['type'] ?? '';
        if (in_array($type, ['Cleaning', 'Personnel Biosecurity Logs', 'Visitors', 'Personnel Entry Logs', 'Weight Sampling'])) {
            // Get house number from ID (only for Cleaning, Personnel, Personnel Entry Logs, and Weight Sampling)
            if (in_array($type, ['Cleaning', 'Personnel Biosecurity Logs', 'Personnel Entry Logs', 'Weight Sampling']) && !empty($validated['house'])) {
                $house = \App\Models\House::find($validated['house']);
                if ($house) {
                    $validated['house'] = $house->house_number;
                }
            }
            
            // Get pen label from ID (only for Cleaning and Weight Sampling)
            if (in_array($type, ['Cleaning', 'Weight Sampling']) && !empty($validated['pen'])) {
                $pen = \App\Models\Pen::find($validated['pen']);
                if ($pen) {
                    $validated['pen'] = $pen->pen_name;
                }
            }
            
            // Get worker name from ID (performed_by for Cleaning, monitored_by for Visitors)
            $workerField = $type === 'Cleaning' ? 'performed_by' : ($type === 'Visitors' ? 'monitored_by' : null);
            if ($workerField && !empty($validated[$workerField])) {
                $worker = \App\Models\Employee::find($validated[$workerField]);
                if ($worker) {
                    $fullName = trim(sprintf(
                        '%s %s %s %s',
                        $worker->FirstName ?? '',
                        $worker->MiddleName ?? '',
                        $worker->LastName ?? '',
                        $worker->Suffix ?? ''
                    ));
                    $validated[$workerField] = $fullName ?: $worker->EmployeeId;
                }
            }
        }

        $log = BiosecurityLog::create($validated);

        return response()->json([
            'message' => 'Log created successfully',
            'log' => $this->formatLogForDisplay($log),
        ], 201);
    }

    /**
     * Update an existing biosecurity log
     */
    public function update(Request $request, $id)
    {
        $log = BiosecurityLog::findOrFail($id);

        $validated = $request->validate([
            'type' => 'sometimes|string|max:50',
            'house' => 'nullable|string|max:50',
            'pen' => 'nullable|string|max:50',
            'activity' => 'nullable|string|max:255',
            'disinfectant_used' => 'nullable|string|max:255',
            'performed_by' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:100',
            'role' => 'nullable|string|max:100',
            'date' => 'nullable|date',
            'time' => 'nullable',
            'foot_bath' => 'nullable|string|max:10',
            'boots_changed' => 'nullable|string|max:10',
            'protective_clothing' => 'nullable|string|max:10',
            'time_in' => 'nullable',
            'time_out' => 'nullable',
            'purpose' => 'nullable|string|max:255',
            'sanitation' => 'nullable|string|max:10',
            'ppe' => 'nullable|string|max:10',
            'monitored_by' => 'nullable|string|max:100',
            'batch' => 'nullable|string|max:50',
            'flocks_with_cases' => 'nullable|string|max:50',
            'age' => 'nullable|string|max:50',
            'average_weight' => 'nullable|string|max:50',
            'target' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        // Convert IDs to display values for Cleaning, Personnel Biosecurity Logs, Visitors, Personnel Entry Logs, and Weight Sampling
        $type = $validated['type'] ?? $log->type;
        if (in_array($type, ['Cleaning', 'Personnel Biosecurity Logs', 'Visitors', 'Personnel Entry Logs', 'Weight Sampling'])) {
            // Get house number from ID (only for Cleaning, Personnel, Personnel Entry Logs, and Weight Sampling)
            if (in_array($type, ['Cleaning', 'Personnel Biosecurity Logs', 'Personnel Entry Logs', 'Weight Sampling']) && !empty($validated['house'])) {
                $house = \App\Models\House::find($validated['house']);
                if ($house) {
                    $validated['house'] = $house->house_number;
                }
            }
            
            // Get pen label from ID (only for Cleaning and Weight Sampling)
            if (in_array($type, ['Cleaning', 'Weight Sampling']) && !empty($validated['pen'])) {
                $pen = \App\Models\Pen::find($validated['pen']);
                if ($pen) {
                    $validated['pen'] = $pen->pen_name;
                }
            }
            
            // Get worker name from ID (performed_by for Cleaning, monitored_by for Visitors)
            $workerField = $type === 'Cleaning' ? 'performed_by' : ($type === 'Visitors' ? 'monitored_by' : null);
            if ($workerField && !empty($validated[$workerField])) {
                $worker = \App\Models\Employee::find($validated[$workerField]);
                if ($worker) {
                    $fullName = trim(sprintf(
                        '%s %s %s %s',
                        $worker->FirstName ?? '',
                        $worker->MiddleName ?? '',
                        $worker->LastName ?? '',
                        $worker->Suffix ?? ''
                    ));
                    $validated[$workerField] = $fullName ?: $worker->EmployeeId;
                }
            }
        }

        $log->update($validated);

        return response()->json([
            'message' => 'Log updated successfully',
            'log' => $this->formatLogForDisplay($log),
        ]);
    }

    /**
     * Delete a biosecurity log
     */
    public function destroy($id)
    {
        $log = BiosecurityLog::findOrFail($id);
        $log->delete();

        return response()->json([
            'message' => 'Log deleted successfully',
        ]);
    }

    /**
     * Get overview statistics
     */
    private function getOverview()
    {
        // Count violations (placeholder - customize as needed)
        $violations = 0;

        // Get last disinfection log
        $lastDisinfection = BiosecurityLog::where('type', 'Cleaning')
            ->whereNotNull('activity')
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->first();

        // Count visitors
        $visitors = BiosecurityLog::where('type', 'Visitors')->count();

        // Count mortalities (placeholder)
        $mortalities = 0;

        return [
            'violations' => $violations,
            'last_disinfection' => $lastDisinfection ? [
                'date' => $lastDisinfection->date ? $lastDisinfection->date->format('m-d-y') : '--',
                'time' => $lastDisinfection->time ? \Carbon\Carbon::parse($lastDisinfection->time)->format('h:i A') : '--',
            ] : ['date' => '--', 'time' => '--'],
            'visitors' => $visitors,
            'mortalities' => $mortalities,
        ];
    }

    /**
     * Format log for display in the table
     */
    private function formatLogForDisplay($log)
    {
        return [
            'id' => $log->id,
            'type' => $log->type,
            'house' => $log->house,
            'pen' => $log->pen,
            'activity' => $log->activity,
            'disinfectant_used' => $log->disinfectant_used,
            'performed_by' => $log->performed_by,
            'name' => $log->name,
            'role' => $log->role,
            'date' => $log->date ? $log->date->format('m-d-y') : '',
            'time' => $log->time ? \Carbon\Carbon::parse($log->time)->format('h:i A') : '',
            'foot_bath' => $log->foot_bath,
            'boots_changed' => $log->boots_changed,
            'protective_clothing' => $log->protective_clothing,
            'time_in' => $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('h:i A') : '',
            'time_out' => $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('h:i A') : '',
            'purpose' => $log->purpose,
            'sanitation' => $log->sanitation,
            'ppe' => $log->ppe,
            'monitored_by' => $log->monitored_by,
            'batch' => $log->batch,
            'flocks_with_cases' => $log->flocks_with_cases,
            'age' => $log->age,
            'average_weight' => $log->average_weight,
            'target' => $log->target,
            'status' => $log->status,
        ];
    }
}