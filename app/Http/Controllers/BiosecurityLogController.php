<?php

namespace App\Http\Controllers;

use App\Models\CleaningLog;
use App\Models\PersonnelBiosecurityLog;
use App\Models\VisitorLog;
use App\Models\PersonnelEntryLog;
use App\Models\WeightSamplingLog;
use Illuminate\Http\Request;

class BiosecurityLogController extends Controller
{
    /**
     * Get all biosecurity logs grouped by type
     */
    public function index()
    {
        $groupedLogs = [
            'Cleaning' => CleaningLog::orderBy('created_at', 'desc')->get()->map(fn($log) => $this->formatCleaningLog($log)),
            'Personnel Biosecurity Logs' => PersonnelEntryLog::orderBy('created_at', 'desc')->get()->map(fn($log) => $this->formatPersonnelEntryLog($log)),
            'Visitors' => VisitorLog::orderBy('created_at', 'desc')->get()->map(fn($log) => $this->formatVisitorLog($log)),
            'Weight Sampling' => WeightSamplingLog::orderBy('created_at', 'desc')->get()->map(fn($log) => $this->formatWeightSamplingLog($log)),
        ];

        // Get overview stats
        $overview = $this->getOverview($groupedLogs);

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
        $type = $request->input('type', 'Cleaning');

        $validated = $request->validate($this->getValidationRules($type));

        // Convert IDs to display values
        $validated = $this->convertIdsToValues($type, $validated);

        $log = $this->createLog($type, $validated);

        return response()->json([
            'message' => 'Log created successfully',
            'log' => $this->formatLogByType($type, $log),
        ], 201);
    }

    /**
     * Update an existing biosecurity log
     */
    public function update(Request $request, $id)
    {
        $type = $request->input('type');

        // Find the log based on type
        $log = $this->findLog($type, $id);

        $validated = $request->validate($this->getValidationRules($type, true));

        // Convert IDs to display values
        $validated = $this->convertIdsToValues($type, $validated, $log);

        $log->update($validated);

        return response()->json([
            'message' => 'Log updated successfully',
            'log' => $this->formatLogByType($type, $log),
        ]);
    }

    /**
     * Delete a biosecurity log
     */
    public function destroy(Request $request, $id)
    {
        $type = $request->query('type', 'Cleaning');

        $log = $this->findLog($type, $id);
        $log->delete();

        return response()->json([
            'message' => 'Log deleted successfully',
        ]);
    }

    /**
     * Get validation rules based on type
     */
    private function getValidationRules($type, $isUpdate = false)
    {
        $rules = [
            'Cleaning' => [
                'house' => 'nullable|string|max:50',
                'pen' => 'nullable|string|max:50',
                'activity' => 'nullable|string|max:255',
                'disinfectant_used' => 'nullable|string|max:255',
                'performed_by' => 'nullable|string|max:100',
                'date' => 'nullable|date',
                'time' => 'nullable',
            ],
            'Personnel Biosecurity Logs' => [
                'name' => 'nullable|string|max:100',
                'role' => 'nullable|string|max:100',
                'date' => 'nullable|date',
                'time' => 'nullable',
                'status' => 'nullable|string|max:20',
            ],
            'Visitors' => [
                'date' => 'nullable|date',
                'time_in' => 'nullable',
                'time_out' => 'nullable',
                'name' => 'nullable|string|max:100',
                'purpose' => 'nullable|string|max:255',
                'foot_bath' => 'nullable|string|max:10',
                'sanitation' => 'nullable|string|max:10',
                'ppe' => 'nullable|string|max:10',
                'monitored_by' => 'nullable|string|max:100',
            ],
            'Personnel Entry Logs' => [
                'name' => 'nullable|string|max:100',
                'role' => 'nullable|string|max:100',
                'date' => 'nullable|date',
                'time' => 'nullable',
                'status' => 'nullable|string|max:20',
            ],
            'Weight Sampling' => [
                'date' => 'nullable|date',
                'time' => 'nullable',
                'house' => 'nullable|string|max:50',
                'pen' => 'nullable|string|max:50',
                'batch' => 'nullable|string|max:50',
                'flocks_with_cases' => 'nullable|string|max:50',
                'age' => 'nullable|string|max:50',
                'average_weight' => 'nullable|string|max:50',
                'target' => 'nullable|string|max:50',
                'status' => 'nullable|string|max:50',
            ],
        ];

        return $rules[$type] ?? [];
    }

    /**
     * Convert IDs to display values
     */
    private function convertIdsToValues($type, $validated, $existingLog = null)
    {
        // House ID to house number
        if (in_array($type, ['Cleaning', 'Weight Sampling']) && !empty($validated['house'])) {
            $house = \App\Models\House::find($validated['house']);
            if ($house) {
                $validated['house'] = $house->house_number;
            }
        }

        // Pen ID to pen name
        if (in_array($type, ['Cleaning', 'Weight Sampling']) && !empty($validated['pen'])) {
            $pen = \App\Models\Pen::find($validated['pen']);
            if ($pen) {
                $validated['pen'] = $pen->pen_name;
            }
        }

        // Employee ID to name
        $workerField = null;
        if ($type === 'Cleaning') {
            $workerField = 'performed_by';
        } elseif ($type === 'Visitors') {
            $workerField = 'monitored_by';
        }

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

        return $validated;
    }

    /**
     * Create log based on type
     */
    private function createLog($type, $validated)
    {
        return match ($type) {
            'Cleaning' => CleaningLog::create($validated),
            'Personnel Biosecurity Logs' => PersonnelEntryLog::create($validated),
            'Visitors' => VisitorLog::create($validated),
            'Personnel Entry Logs' => PersonnelEntryLog::create($validated),
            'Weight Sampling' => WeightSamplingLog::create($validated),
            default => throw new \InvalidArgumentException("Unknown log type: $type"),
        };
    }

    /**
     * Find log based on type
     */
    private function findLog($type, $id)
    {
        return match ($type) {
            'Cleaning' => CleaningLog::findOrFail($id),
            'Personnel Biosecurity Logs' => PersonnelEntryLog::findOrFail($id),
            'Visitors' => VisitorLog::findOrFail($id),
            'Personnel Entry Logs' => PersonnelEntryLog::findOrFail($id),
            'Weight Sampling' => WeightSamplingLog::findOrFail($id),
            default => throw new \InvalidArgumentException("Unknown log type: $type"),
        };
    }

    /**
     * Format log based on type
     */
    private function formatLogByType($type, $log)
    {
        return match ($type) {
            'Cleaning' => $this->formatCleaningLog($log),
            'Personnel Biosecurity Logs' => $this->formatPersonnelEntryLog($log),
            'Visitors' => $this->formatVisitorLog($log),
            'Personnel Entry Logs' => $this->formatPersonnelEntryLog($log),
            'Weight Sampling' => $this->formatWeightSamplingLog($log),
            default => $log,
        };
    }

    private function formatCleaningLog($log)
    {
        return [
            'id' => $log->id,
            'type' => 'Cleaning',
            'house' => $log->house,
            'pen' => $log->pen,
            'activity' => $log->activity,
            'disinfectant_used' => $log->disinfectant_used,
            'performed_by' => $log->performed_by,
            'date' => $log->date ? $log->date->format('m-d-y') : '',
            'time' => $log->time ? \Carbon\Carbon::parse($log->time)->format('h:i A') : '',
        ];
    }

    private function formatPersonnelBiosecurityLog($log)
    {
        return [
            'id' => $log->id,
            'type' => 'Personnel Biosecurity Logs',
            'name' => $log->name,
            'role' => $log->role,
            'date' => $log->date ? $log->date->format('m-d-y') : '',
            'time' => $log->time ? \Carbon\Carbon::parse($log->time)->format('h:i A') : '',
            'status' => $log->status ?? '',
        ];
    }

    private function formatVisitorLog($log)
    {
        return [
            'id' => $log->id,
            'type' => 'Visitors',
            'date' => $log->date ? $log->date->format('m-d-y') : '',
            'time_in' => $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('h:i A') : '',
            'time_out' => $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('h:i A') : '',
            'name' => $log->name,
            'purpose' => $log->purpose,
            'foot_bath' => $log->foot_bath,
            'sanitation' => $log->sanitation,
            'ppe' => $log->ppe,
            'monitored_by' => $log->monitored_by,
        ];
    }

    private function formatPersonnelEntryLog($log)
    {
        return [
            'id' => $log->id,
            'type' => 'Personnel Entry Logs',
            'name' => $log->name,
            'role' => $log->role,
            'date' => $log->date ? $log->date->format('m-d-y') : '',
            'time' => $log->time ? \Carbon\Carbon::parse($log->time)->format('h:i A') : '',
            'status' => $log->status ?? '',
        ];
    }

    private function formatWeightSamplingLog($log)
    {
        return [
            'id' => $log->id,
            'type' => 'Weight Sampling',
            'date' => $log->date ? $log->date->format('m-d-y') : '',
            'time' => $log->time ? \Carbon\Carbon::parse($log->time)->format('h:i A') : '',
            'house' => $log->house,
            'pen' => $log->pen,
            'batch' => $log->batch,
            'flocks_with_cases' => $log->flocks_with_cases,
            'age' => $log->age,
            'average_weight' => $log->average_weight,
            'target' => $log->target,
            'status' => $log->status,
        ];
    }

    /**
     * Get overview statistics
     */
    private function getOverview($groupedLogs)
    {
        $violations = 0;
        $visitors = count($groupedLogs['Visitors']);
        $mortalities = 0;

        // Get last disinfection log
        $lastDisinfection = CleaningLog::whereNotNull('activity')
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->first();

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
}