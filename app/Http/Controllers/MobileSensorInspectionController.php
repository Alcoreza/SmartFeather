<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileSensorInspectionController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'task_id' => 'required|integer|exists:tasks,taskid',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'sensor_present' => 'required|boolean',
            'sensor_clean_unblocked' => 'required|boolean',
            'no_visible_damage_or_loose_wiring' => 'required|boolean',
            'power_status_on' => 'required|boolean',
            'placement_secure' => 'required|boolean',
            'recorded_at' => 'required|date',
        ]);

        $task = DB::table('tasks')
            ->where('taskid', $validated['task_id'])
            ->where('user_employeeid', $validated['employee_id'])
            ->first();

        if (!$task) {
            return response()->json([
                'message' => 'Assigned task was not found for this employee.',
            ], 404);
        }

        if ((string) $task->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending tasks can be submitted for sensor inspection.',
            ], 422);
        }

        $normalizedTaskType = strtolower(trim((string) $task->tasktype));
        if ($normalizedTaskType !== 'sensor inspection') {
            return response()->json([
                'message' => 'This task is not assigned as a sensor inspection task.',
            ], 422);
        }

        if ((int) $task->house_houseid !== (int) $validated['house_id']) {
            return response()->json([
                'message' => 'Selected house does not match the assigned task house.',
            ], 403);
        }

        if ((int) $task->pennumber !== (int) $validated['pen_id']) {
            return response()->json([
                'message' => 'Selected pen does not match the assigned task pen.',
            ], 403);
        }

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before recording sensor inspection.',
            ], 403);
        }

        $taskBiosecurity = DB::table('personnel_biosecurity_logs')
            ->where('employee_id', $validated['employee_id'])
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->where('task_id', $validated['task_id'])
            ->where('house_id', $validated['house_id'])
            ->where('pen_id', $validated['pen_id'])
            ->orderByDesc('id')
            ->first();

        if (!$taskBiosecurity) {
            return response()->json([
                'message' => 'Please complete personnel biosecurity for this assigned task before submitting sensor inspection.',
            ], 403);
        }

        $sensorPresent = filter_var($validated['sensor_present'], FILTER_VALIDATE_BOOLEAN);
        $sensorCleanUnblocked = filter_var($validated['sensor_clean_unblocked'], FILTER_VALIDATE_BOOLEAN);
        $noVisibleDamageOrLooseWiring = filter_var($validated['no_visible_damage_or_loose_wiring'], FILTER_VALIDATE_BOOLEAN);
        $powerStatusOn = filter_var($validated['power_status_on'], FILTER_VALIDATE_BOOLEAN);
        $placementSecure = filter_var($validated['placement_secure'], FILTER_VALIDATE_BOOLEAN);

        DB::statement(
            'insert into sensor_inspection_logs (
                task_id,
                employee_id,
                house_id,
                pen_id,
                sensor_present,
                sensor_clean_unblocked,
                no_visible_damage_or_loose_wiring,
                power_status_on,
                placement_secure,
                recorded_at,
                created_at,
                updated_at
            ) values (?, ?, ?, ?, ?::boolean, ?::boolean, ?::boolean, ?::boolean, ?::boolean, ?, now(), now())',
            [
                $validated['task_id'],
                $validated['employee_id'],
                $validated['house_id'],
                $validated['pen_id'],
                $sensorPresent ? 'true' : 'false',
                $sensorCleanUnblocked ? 'true' : 'false',
                $noVisibleDamageOrLooseWiring ? 'true' : 'false',
                $powerStatusOn ? 'true' : 'false',
                $placementSecure ? 'true' : 'false',
                $validated['recorded_at'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Sensor inspection submitted successfully.',
        ]);
    }
}