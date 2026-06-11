<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobilePersonnelLogsController extends Controller
{
    public function context(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $employeeId)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry) {
            return response()->json([
                'message' => 'No fingerprint scan found for this worker.',
            ], 404);
        }

        if (strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Worker is currently OUT. Please scan IN first before completing personnel biosecurity logs.',
            ], 403);
        }

        $previousBiosecurity = DB::table('personnel_biosecurity_logs')
            ->where('employee_id', $employeeId)
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->orderByDesc('id')
            ->first();

        $houses = DB::table('house')
            ->orderBy('id', 'asc')
            ->get(['id', 'house_number'])
            ->map(fn($house) => [
                'id' => (int) $house->id,
                'house_number' => $house->house_number,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'employee_id' => (int) $latestEntry->employee_id,
            'personnel_entry_log_id' => (int) $latestEntry->id,
            'name' => $latestEntry->name,
            'role' => $latestEntry->role,
            'status' => $latestEntry->status,
            'date' => !empty($latestEntry->date)
                ? Carbon::parse($latestEntry->date)->format('Y-m-d')
                : '',
            'time' => !empty($latestEntry->time)
                ? Carbon::parse((string) $latestEntry->time)->format('H:i:s')
                : '',
            'houses' => $houses,
            'previous_biosecurity' => $previousBiosecurity ? [
                'house_id' => $previousBiosecurity->house_id ? (int) $previousBiosecurity->house_id : null,
                'pen_id' => $previousBiosecurity->pen_id ? (int) $previousBiosecurity->pen_id : null,
                'foot_bath' => $this->isYes($previousBiosecurity->foot_bath ?? null),
                'boots_changed' => $this->isYes($previousBiosecurity->boots_changed ?? null),
                'protective_clothing' => $this->isYes($previousBiosecurity->protective_clothing ?? null),
            ] : null,
        ]);
    }

    public function submit(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $validated = $request->validate([
            'personnel_entry_log_id' => 'required|integer|exists:personnel_entry_logs,id',
            'task_id' => 'nullable|integer|exists:tasks,taskid',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'nullable|integer|exists:pen,id',
            'foot_bath' => 'required|boolean',
            'boots_changed' => 'required|boolean',
            'protective_clothing' => 'required|boolean',
        ]);

        if (!$validated['foot_bath'] || !$validated['boots_changed'] || !$validated['protective_clothing']) {
            return response()->json([
                'message' => 'All biosecurity checks must be completed before submission.',
            ], 422);
        }

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('id', $validated['personnel_entry_log_id'])
            ->where('employee_id', $employeeId)
            ->first();

        if (!$latestEntry) {
            return response()->json([
                'message' => 'Personnel entry log not found for this worker.',
            ], 404);
        }

        if (strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Worker is currently OUT. Please scan IN first before submitting personnel biosecurity logs.',
            ], 403);
        }

        $selectedHouse = DB::table('house')
            ->where('id', $validated['house_id'])
            ->first();

        if (!$selectedHouse) {
            return response()->json([
                'message' => 'Selected house was not found.',
            ], 404);
        }

        if (!empty($validated['pen_id'])) {
            $selectedPen = DB::table('pen')
                ->where('id', $validated['pen_id'])
                ->where('house_id', $validated['house_id'])
                ->first();

            if (!$selectedPen) {
                return response()->json([
                    'message' => 'Selected pen does not belong to the assigned house.',
                ], 422);
            }
        }

        if (!empty($validated['task_id'])) {
            $task = DB::table('tasks')
                ->where('taskid', $validated['task_id'])
                ->where('user_employeeid', $employeeId)
                ->where('status', 'Pending')
                ->first();

            if (!$task) {
                return response()->json([
                    'message' => 'Pending task not found for this worker.',
                ], 404);
            }

            if ((int) $task->house_houseid !== (int) $validated['house_id']) {
                return response()->json([
                    'message' => 'This biosecurity log must use the task assigned house.',
                ], 422);
            }

            if (!empty($task->pennumber) && (int) $task->pennumber !== (int) ($validated['pen_id'] ?? 0)) {
                return response()->json([
                    'message' => 'This biosecurity log must use the task assigned pen.',
                ], 422);
            }
        }

        $validBiosecurityFrom = now()->subHours(24);

        $alreadySubmittedQuery = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->where('employee_id', $employeeId)
            ->where('created_at', '>=', $validBiosecurityFrom);

        if (!empty($validated['task_id'])) {
            $alreadySubmittedQuery->where('task_id', $validated['task_id']);
        } else {
            $alreadySubmittedQuery->whereNull('task_id');
        }

        if ($alreadySubmittedQuery->exists()) {
            return response()->json([
                'success' => true,
                'message' => !empty($validated['task_id'])
                    ? 'Biosecurity already completed for this task.'
                    : 'Personnel biosecurity log already submitted for this scan.',
            ]);
        }

        DB::table('personnel_biosecurity_logs')->insert([
            'employee_id' => $employeeId,
            'personnel_entry_log_id' => $latestEntry->id,
            'task_id' => $validated['task_id'] ?? null,
            'name' => $latestEntry->name,
            'role' => $latestEntry->role,
            'house_id' => $selectedHouse->id,
            'pen_id' => $validated['pen_id'] ?? null,
            'house' => $selectedHouse->house_number,
            'date' => $latestEntry->date,
            'time' => $latestEntry->time,
            'foot_bath' => 'Yes',
            'boots_changed' => 'Yes',
            'protective_clothing' => 'Yes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => !empty($validated['task_id'])
                ? 'Biosecurity completed. You may now continue with the assigned task.'
                : 'Personnel biosecurity log submitted successfully.',
        ]);
    }

    private function isYes($value): bool
    {
        return strtolower(trim((string) $value)) === 'yes' || $value === true || $value === 1;
    }
}