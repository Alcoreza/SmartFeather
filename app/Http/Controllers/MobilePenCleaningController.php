<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobilePenCleaningController extends Controller
{
    public function getContext(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $employeeId)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before accessing pen cleaning.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 403);
        }

        $latestBiosecurity = DB::table('personnel_biosecurity_logs')
            ->where('employee_id', $employeeId)
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->orderByDesc('id')
            ->first();

        if (!$latestBiosecurity) {
            return response()->json([
                'message' => 'Please submit the personnel biosecurity form first before accessing pen cleaning.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 403);
        }

        $house = House::find($latestBiosecurity->house_id);

        if (!$house) {
            return response()->json([
                'message' => 'Assigned house from personnel biosecurity was not found.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 422);
        }

        $pens = Pen::where('house_id', $house->id)
            ->orderBy('id', 'asc')
            ->get(['id', 'pen_name'])
            ->map(function (Pen $pen) {
                return [
                    'id' => $pen->id,
                    'pen_name' => $pen->pen_name,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'access_allowed' => true,
            'house_id' => $house->id,
            'house_number' => $house->house_number,
            'pen_options' => $pens,
        ]);
    }

    public function submit(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $validated = $request->validate([
            'task_id' => 'nullable|integer|exists:tasks,taskid',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'materials_used' => 'required|string|max:255',
            'recorded_date' => 'required|date',
            'recorded_time' => 'required|date_format:H:i:s',
        ]);

        if (!empty($validated['task_id'])) {
            $task = DB::table('tasks')
                ->where('taskid', $validated['task_id'])
                ->where('user_employeeid', $employeeId)
                ->first();

            if (!$task) {
                return response()->json([
                    'message' => 'Assigned task was not found for this employee.',
                ], 404);
            }

            if ((string) $task->status !== 'Pending') {
                return response()->json([
                    'message' => 'Only pending tasks can be submitted for pen cleaning.',
                ], 422);
            }

            $normalizedTaskType = strtolower(trim((string) $task->tasktype));
            if ($normalizedTaskType !== 'pen cleaning') {
                return response()->json([
                    'message' => 'This task is not assigned as a pen cleaning task.',
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
        }

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $employeeId)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before recording pen cleaning.',
            ], 403);
        }

        $biosecurityQuery = DB::table('personnel_biosecurity_logs')
            ->where('employee_id', $employeeId)
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->where('house_id', $validated['house_id']);

        if (!empty($validated['task_id'])) {
            $biosecurityQuery
                ->where('task_id', $validated['task_id'])
                ->where('pen_id', $validated['pen_id']);
        }

        $latestBiosecurity = $biosecurityQuery
            ->orderByDesc('id')
            ->first();

        if (!$latestBiosecurity) {
            return response()->json([
                'message' => !empty($validated['task_id'])
                    ? 'Please complete personnel biosecurity for this assigned task before submitting pen cleaning.'
                    : 'Please submit the personnel biosecurity form first before recording pen cleaning.',
            ], 403);
        }

        $house = House::find($validated['house_id']);
        $pen = Pen::where('id', $validated['pen_id'])
            ->where('house_id', $validated['house_id'])
            ->first();

        if (!$pen) {
            return response()->json([
                'message' => 'Selected pen does not belong to the selected house.',
            ], 422);
        }

        $employee = Employee::find($employeeId);

        $performedBy = null;
        if ($employee) {
            $performedBy = trim(sprintf(
                '%s %s %s %s',
                $employee->FirstName ?? '',
                $employee->MiddleName ?? '',
                $employee->LastName ?? '',
                $employee->Suffix ?? '',
            ));
        }

        $createdAt = $validated['recorded_date'] . ' ' . $validated['recorded_time'];

        DB::table('cleaning_logs')->insert([
            'task_id' => $validated['task_id'] ?? null,
            'house_id' => $validated['house_id'],
            'pen_id' => $validated['pen_id'],
            'house' => $house?->house_number,
            'pen' => $pen->pen_name,
            'activity' => 'Pen Cleaning',
            'disinfectant_used' => $validated['materials_used'],
            'performed_by' => $performedBy ?: (string) $employeeId,
            'date' => $validated['recorded_date'],
            'time' => $validated['recorded_time'],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return response()->json([
            'success' => true,
            'message' => !empty($validated['task_id'])
                ? 'Pen cleaning submitted successfully.'
                : 'Pen cleaning record submitted successfully.',
        ]);
    }
}