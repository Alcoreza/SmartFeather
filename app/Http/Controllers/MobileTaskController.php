<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
                    'photourl' => $this->buildTaskPhotoUrl($task->photourl),
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
