<?php

namespace App\Http\Controllers;

use App\Models\PersonnelBiosecurityLog;
use App\Models\VisitorLog;
use App\Models\PersonnelEntryLog;
use Illuminate\Http\Request;

class BiosecurityLogController extends Controller
{
    /**
     * Get all biosecurity logs grouped by type
     */
    public function index()
    {
        $groupedLogs = [
            'Personnel Biosecurity Logs' => PersonnelEntryLog::orderBy('created_at', 'desc')->get()->map(fn($log) => $this->formatPersonnelEntryLog($log)),
            'Visitors' => VisitorLog::orderBy('created_at', 'desc')->get()->map(fn($log) => $this->formatVisitorLog($log)),
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
        $type = $request->input('type', 'Personnel Biosecurity Logs');

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
        $type = $request->query('type', 'Personnel Biosecurity Logs');

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
        ];

        return $rules[$type] ?? [];
    }

    /**
     * Convert IDs to display values
     */
    private function convertIdsToValues($type, $validated, $existingLog = null)
    {

        // Employee ID to name
        $workerField = null;
        if ($type === 'Visitors') {
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
            'Personnel Biosecurity Logs' => PersonnelEntryLog::create($validated),
            'Visitors' => VisitorLog::create($validated),
            'Personnel Entry Logs' => PersonnelEntryLog::create($validated),
            default => throw new \InvalidArgumentException("Unknown log type: $type"),
        };
    }

    /**
     * Find log based on type
     */
    private function findLog($type, $id)
    {
        return match ($type) {
            'Personnel Biosecurity Logs' => PersonnelEntryLog::findOrFail($id),
            'Visitors' => VisitorLog::findOrFail($id),
            'Personnel Entry Logs' => PersonnelEntryLog::findOrFail($id),
            default => throw new \InvalidArgumentException("Unknown log type: $type"),
        };
    }

    /**
     * Format log based on type
     */
    private function formatLogByType($type, $log)
    {
        return match ($type) {
            'Personnel Biosecurity Logs' => $this->formatPersonnelEntryLog($log),
            'Visitors' => $this->formatVisitorLog($log),
            'Personnel Entry Logs' => $this->formatPersonnelEntryLog($log),
            default => $log,
        };
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

    /**
     * Get overview statistics
     */
    private function getOverview($groupedLogs)
    {
        $violations = 0;
        $visitors = count($groupedLogs['Visitors']);
        $mortalities = 0;

        return [
            'violations' => $violations,
            'last_disinfection' => ['date' => '--', 'time' => '--'],
            'visitors' => $visitors,
            'mortalities' => $mortalities,
        ];
    }
}