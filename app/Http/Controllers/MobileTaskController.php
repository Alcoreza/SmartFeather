<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MobileTaskController extends Controller
{
    public function getFlockmanTasks(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer',
        ]);

        $tasks = Task::query()
            ->leftJoin('house', 'tasks.house_houseid', '=', 'house.id')
            ->leftJoin('pen', function ($join) {
                $join->on('tasks.house_houseid', '=', 'pen.house_id')
                    ->on('tasks.pennumber', '=', 'pen.id');
            })
            ->where('tasks.user_employeeid', $validated['employee_id'])
            ->orderByDesc('tasks.timeassigned')
            ->get([
                'tasks.taskid',
                'tasks.tasktype',
                'tasks.detailedtask',
                'tasks.timeassigned',
                'tasks.finishby',
                'tasks.status',
                'tasks.notes',
                'tasks.time_completed',
                'tasks.user_employeeid',
                'tasks.house_houseid',
                'tasks.pennumber',
                'tasks.prioritylevel',
                'tasks.photourl',
                'house.house_number as house_number',
                'pen.pen_name as pen_name',
            ])
            ->map(function ($task) {
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
                    'photourl' => $this->buildTaskPhotoUrl($task->photourl),
                    'house_number' => $task->house_number,
                    'pen_name' => $task->pen_name,
                ];
            })
            ->values();

        return response()->json($tasks);
    }

    public function createTaskPhotoUploadUrl(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,taskid',
            'employee_id' => 'required|integer',
            'mime_type' => 'required|string|in:image/jpeg,image/png,image/webp',
        ]);

        $task = Task::where('taskid', $validated['task_id'])
            ->where('user_employeeid', $validated['employee_id'])
            ->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found for this employee.'
            ], 404);
        }

        $bucket = config('services.supabase.task_photos_bucket');
        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $serviceRoleKey = config('services.supabase.service_role_key');

        if (blank($bucket) || blank($baseUrl) || blank($serviceRoleKey)) {
            return response()->json([
                'message' => 'Supabase storage is not configured correctly.'
            ], 500);
        }

        $extension = match ($validated['mime_type']) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $path = sprintf(
            'employee-%d/task-%d/%s.%s',
            $validated['employee_id'],
            $validated['task_id'],
            Str::uuid()->toString(),
            $extension
        );

        $endpoint = sprintf(
            '%s/storage/v1/object/upload/sign/%s/%s',
            $baseUrl,
            $bucket,
            $path
        );

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $serviceRoleKey,
            'apikey' => $serviceRoleKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
                    'expiresIn' => 600,
                ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to create signed upload URL.',
                'details' => $response->body(),
            ], 500);
        }

        $payload = $response->json();
        $token = $payload['token'] ?? null;

        if (blank($token)) {
            return response()->json([
                'message' => 'Supabase did not return an upload token.',
                'details' => $payload,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'bucket' => $bucket,
            'path' => $path,
            'token' => $token,
            'public_url' => $this->buildTaskPhotoUrl($path),
        ]);
    }

    public function submitTaskForApproval(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,taskid',
            'employee_id' => 'required|integer',
            'notes' => 'nullable|string',
            'photo_path' => 'nullable|string',
        ]);

        $task = Task::where('taskid', $validated['task_id'])
            ->where('user_employeeid', $validated['employee_id'])
            ->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found for this employee.'
            ], 404);
        }

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before submitting this task.'
            ], 403);
        }

        $hasBiosecuritySubmission = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->exists();

        if (!$hasBiosecuritySubmission) {
            return response()->json([
                'message' => 'Please submit the personnel biosecurity form first before submitting this task.'
            ], 403);
        }

        if (
            !empty($task->house_houseid) &&
            (int) $task->house_houseid !== (int) $latestEntry->house_id
        ) {
            return response()->json([
                'message' => 'This task is assigned to a different house. Please go to the house from your assigned task or scan the correct house first.'
            ], 403);
        }

        $task->status = 'For Approval';
        $task->notes = $validated['notes'] ?? null;
        $task->photourl = $validated['photo_path'] ?? null;
        $task->time_completed = now();
        $task->save();

        return response()->json([
            'success' => true,
            'message' => 'Task submitted for approval.',
            'photo_url' => $this->buildTaskPhotoUrl($task->photourl),
        ]);
    }

    private function buildTaskPhotoUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $bucket = config('services.supabase.task_photos_bucket');

        return sprintf(
            '%s/storage/v1/object/public/%s/%s',
            $baseUrl,
            $bucket,
            ltrim($path, '/')
        );
    }

    private function formatDateTimeForMobile($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d\TH:i:s');
    }
}