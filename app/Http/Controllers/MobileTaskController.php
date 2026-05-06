<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MobileTaskController extends Controller
{
    public function getFlockmanTasks(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer',
        ]);

        $tasks = Task::where('user_employeeid', $validated['employee_id'])
            ->orderByDesc('timeassigned')
            ->get()
            ->map(function (Task $task) {
                return [
                    'taskid' => $task->taskid,
                    'tasktype' => $task->tasktype,
                    'detailedtask' => $task->detailedtask,
                    'timeassigned' => $this->formatDateTimeForMobile($task->timeassigned),
                    'finishby' => $this->formatDateTimeForMobile($task->finishby),
                    'status' => $task->status,
                    'notes' => $task->notes,
                    'time_completed' => $this->formatDateTimeForMobile($task->time_completed),
                    'user_employeeid' => $task->user_employeeid,
                    'house_houseid' => $task->house_houseid,
                    'pennumber' => $task->pennumber,
                    'prioritylevel' => $task->prioritylevel,
                    'photourl' => $task->photourl,
                ];
            })
            ->values();

        return response()->json($tasks);
    }

    public function submitTaskForApproval(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,taskid',
            'employee_id' => 'required|integer',
            'notes' => 'nullable|string',
            'photo_url' => 'nullable|string',
        ]);

        $task = Task::where('taskid', $validated['task_id'])
            ->where('user_employeeid', $validated['employee_id'])
            ->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found for this employee.'
            ], 404);
        }

        $task->status = 'For Approval';
        $task->notes = $validated['notes'] ?? null;
        $task->photourl = $validated['photo_url'] ?? null;
        $task->time_completed = now();
        $task->save();

        return response()->json([
            'success' => true,
            'message' => 'Task submitted for approval.'
        ]);
    }

    private function formatDateTimeForMobile($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d\TH:i:s');
    }
}
