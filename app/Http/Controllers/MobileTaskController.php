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
            'status' => 'nullable|string|in:Pending,For Approval,Completed',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:20',
        ]);

        $employeeId = (int) $validated['employee_id'];
        $status = $validated['status'] ?? 'Pending';
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 10);
        $offset = ($page - 1) * $perPage;

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $employeeId)
            ->whereRaw('UPPER(status) = ?', ['IN'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        $clearedTaskIds = collect();

        if ($latestEntry) {
            $validBiosecurityFrom = Carbon::now()->subHours(24);

            $clearedTaskIds = DB::table('personnel_biosecurity_logs')
                ->where('employee_id', $employeeId)
                ->where('personnel_entry_log_id', $latestEntry->id)
                ->whereNotNull('task_id')
                ->where('created_at', '>=', $validBiosecurityFrom)
                ->pluck('task_id')
                ->map(fn($taskId) => (int) $taskId)
                ->unique()
                ->values();
        }

        $countRows = Task::query()
            ->where('user_employeeid', $employeeId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'pending' => (int) ($countRows['Pending'] ?? 0),
            'for_approval' => (int) ($countRows['For Approval'] ?? 0),
            'completed' => (int) ($countRows['Completed'] ?? 0),
        ];

        $tasks = Task::query()
            ->leftJoin('house', 'tasks.house_houseid', '=', 'house.id')
            ->leftJoin('pen', function ($join) {
                $join->on('tasks.house_houseid', '=', 'pen.house_id')
                    ->on('tasks.pennumber', '=', 'pen.id');
            })
            ->where('tasks.user_employeeid', $employeeId)
            ->where('tasks.status', $status)
            ->orderByDesc('tasks.timeassigned')
            ->offset($offset)
            ->limit($perPage)
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
            ->map(function ($task) use ($clearedTaskIds) {
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
                    'submitted_at' => $this->formatDateTimeForMobile($task->time_completed),
                    'submitted_fields' => [],
                    'house_number' => $task->house_number,
                    'pen_name' => $task->pen_name,
                    'biosecurity_cleared' => $clearedTaskIds->contains((int) $task->taskid),
                ];
            })
            ->values();

        $totalForStatus = match ($status) {
            'For Approval' => $counts['for_approval'],
            'Completed' => $counts['completed'],
            default => $counts['pending'],
        };

        return response()->json([
            'success' => true,
            'tasks' => $tasks,
            'counts' => $counts,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'has_more' => ($page * $perPage) < $totalForStatus,
            ],
        ]);
    }

    public function getSubmittedTaskDetail(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,taskid',
            'employee_id' => 'required|integer',
        ]);

        $task = Task::query()
            ->leftJoin('house', 'tasks.house_houseid', '=', 'house.id')
            ->leftJoin('pen', function ($join) {
                $join->on('tasks.house_houseid', '=', 'pen.house_id')
                    ->on('tasks.pennumber', '=', 'pen.id');
            })
            ->where('tasks.taskid', $validated['task_id'])
            ->where('tasks.user_employeeid', $validated['employee_id'])
            ->first([
                'tasks.taskid',
                'tasks.tasktype',
                'tasks.house_houseid',
                'tasks.pennumber',
                'house.house_number as house_number',
                'pen.pen_name as pen_name',
            ]);

        if (!$task) {
            return response()->json([
                'message' => 'Task not found for this employee.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'submitted_fields' => $this->getSubmittedTaskFields($task),
        ]);
    }

    public function checkTaskAccess(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,taskid',
            'employee_id' => 'required|integer',
        ]);

        $task = Task::where('taskid', $validated['task_id'])
            ->where('user_employeeid', $validated['employee_id'])
            ->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found for this employee.'
            ], 404);
        }

        if ($task->status !== 'Pending') {
            return response()->json([
                'message' => 'This task is no longer pending.'
            ], 403);
        }

        $assignedPenId = $task->pennumber;

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete biosecurity before opening this task.'
            ], 403);
        }

        $validBiosecurityFrom = Carbon::now()->subHours(24);

        $hasBiosecurity = DB::table('personnel_biosecurity_logs')
            ->where('employee_id', $validated['employee_id'])
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->where('task_id', $task->taskid)
            ->where('house_id', $task->house_houseid)
            ->where('created_at', '>=', $validBiosecurityFrom)
            ->when(!empty($assignedPenId), function ($query) use ($assignedPenId) {
                $query->where('pen_id', $assignedPenId);
            })
            ->exists();

        if (!$hasBiosecurity) {
            return response()->json([
                'message' => 'Biosecurity for this task has expired. Please submit the form again before opening this task.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'access_granted' => true,
            'message' => 'Task access granted.'
        ]);
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
            'notes' => 'nullable|string|max:1000',
            'photo_path' => 'nullable|string|max:500',
        ]);

        $task = Task::where('taskid', $validated['task_id'])
            ->where('user_employeeid', $validated['employee_id'])
            ->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found for this employee.'
            ], 404);
        }

        if ($task->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending tasks can be submitted for approval.'
            ], 422);
        }

        $assignedPenId = $task->pennumber;

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

        $taskBiosecurity = DB::table('personnel_biosecurity_logs')
            ->where('employee_id', $validated['employee_id'])
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->where('task_id', $task->taskid)
            ->where('house_id', $task->house_houseid)
            ->when(!empty($assignedPenId), function ($query) use ($assignedPenId) {
                $query->where('pen_id', $assignedPenId);
            })
            ->orderByDesc('id')
            ->first();

        if (!$taskBiosecurity) {
            return response()->json([
                'message' => 'Please complete personnel biosecurity for this assigned task before submitting.'
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

    private function getSubmittedTaskFields($task): array
    {
        $taskType = strtolower(trim((string) $task->tasktype));
        $taskId = (int) $task->taskid;

        $baseFields = [
            ['label' => 'House', 'value' => (string) ($task->house_number ?? '-')],
            ['label' => 'Pen', 'value' => (string) ($task->pen_name ?? '-')],
        ];

        if ($this->isHatchTask($taskType)) {
            $record = DB::table('population_record')
                ->where('task_id', $taskId)
                ->orderByDesc('id')
                ->first();

            if (!$record) {
                return $baseFields;
            }

            return array_merge($baseFields, [
                ['label' => 'Eggs Hatched', 'value' => (string) ($record->eggs_hatched ?? 0)],
                ['label' => 'Mortality', 'value' => (string) ($record->mortality ?? 0)],
                ['label' => 'Running Population', 'value' => (string) ($record->running_population ?? '-')],
                ['label' => 'Started', 'value' => $this->formatSubmittedFieldDateTime($record->recorded_at)],
            ]);
        }

        if ($this->isWeightTask($taskType)) {
            $record = DB::table('weight_sampling_logs')
                ->where('task_id', $taskId)
                ->orderByDesc('id')
                ->first();

            if (!$record) {
                return $baseFields;
            }

            $fields = array_merge($baseFields, [
                ['label' => 'Batch', 'value' => (string) ($record->batch ?? '-')],
                ['label' => 'Age', 'value' => (string) ($record->age ?? '-')],
                ['label' => 'Number of Flocks', 'value' => (string) ($record->number_of_flocks ?? '-')],
                ['label' => 'Flocks With Cases', 'value' => (string) ($record->flocks_with_cases ?? '-')],
                ['label' => 'Average Weight', 'value' => (string) ($record->average_weight ?? '-')],
                ['label' => 'Target Weight', 'value' => (string) ($record->target ?? '-')],
                ['label' => 'Status', 'value' => (string) ($record->status ?? '-')],
                ['label' => 'Started', 'value' => $this->formatSubmittedFieldDateAndTime($record->date ?? null, $record->time ?? null)],
            ]);

            $entries = DB::table('weight_sampling_entries')
                ->where('weight_sampling_log_id', $record->id)
                ->orderBy('sequence_number')
                ->get();

            foreach ($entries as $entry) {
                $fields[] = [
                    'label' => 'Flock ' . $entry->sequence_number . ' Weight',
                    'value' => (string) $entry->weight,
                ];
            }

            return $fields;
        }

        if ($this->isFeedTask($taskType)) {
            $record = DB::table('feed_refill_records')
                ->leftJoin('inventories', 'feed_refill_records.inventory_id', '=', 'inventories.id')
                ->where('feed_refill_records.task_id', $taskId)
                ->orderByDesc('feed_refill_records.id')
                ->first([
                    'feed_refill_records.feeder_number',
                    'feed_refill_records.kilograms_used',
                    'feed_refill_records.recorded_at',
                    'inventories.item_name',
                    'inventories.unit',
                ]);

            if (!$record) {
                return $baseFields;
            }

            return array_merge($baseFields, [
                ['label' => 'Feed Type', 'value' => (string) ($record->item_name ?? '-')],
                ['label' => 'Feeder Number', 'value' => (string) ($record->feeder_number ?? '-')],
                ['label' => 'Kilograms Used', 'value' => trim((string) ($record->kilograms_used ?? '-') . ' ' . (string) ($record->unit ?? 'kg'))],
                ['label' => 'Started', 'value' => $this->formatSubmittedFieldDateTime($record->recorded_at)],
            ]);
        }

        if ($this->isVitaminTask($taskType)) {
            $record = DB::table('vitamin_refill_records')
                ->leftJoin('inventories', 'vitamin_refill_records.inventory_id', '=', 'inventories.id')
                ->where('vitamin_refill_records.task_id', $taskId)
                ->orderByDesc('vitamin_refill_records.id')
                ->first([
                    'vitamin_refill_records.bottles_used',
                    'vitamin_refill_records.recorded_at',
                    'inventories.item_name',
                    'inventories.unit',
                ]);

            if (!$record) {
                return $baseFields;
            }

            return array_merge($baseFields, [
                ['label' => 'Vitamins Type', 'value' => (string) ($record->item_name ?? '-')],
                ['label' => 'Bottles Used', 'value' => trim((string) ($record->bottles_used ?? '-') . ' ' . (string) ($record->unit ?? ''))],
                ['label' => 'Started', 'value' => $this->formatSubmittedFieldDateTime($record->recorded_at)],
            ]);
        }

        if ($this->isCleaningTask($taskType) || $this->isDisinfectionTask($taskType)) {
            $record = DB::table('cleaning_logs')
                ->where('task_id', $taskId)
                ->orderByDesc('id')
                ->first();

            if (!$record) {
                return $baseFields;
            }

            return array_merge($baseFields, [
                ['label' => 'Activity', 'value' => (string) ($record->activity ?? '-')],
                ['label' => 'Material Used', 'value' => (string) ($record->disinfectant_used ?? '-')],
                ['label' => 'Performed By', 'value' => (string) ($record->performed_by ?? '-')],
                ['label' => 'Started', 'value' => $this->formatSubmittedFieldDateAndTime($record->date ?? null, $record->time ?? null)],
            ]);
        }

        if ($this->isSensorTask($taskType)) {
            $record = DB::table('sensor_inspection_logs')
                ->where('task_id', $taskId)
                ->orderByDesc('id')
                ->first();

            if (!$record) {
                return $baseFields;
            }

            return array_merge($baseFields, [
                ['label' => 'Sensor Present', 'value' => $this->yesNo($record->sensor_present ?? false)],
                ['label' => 'Sensor Clean / Unblocked', 'value' => $this->yesNo($record->sensor_clean_unblocked ?? false)],
                ['label' => 'No Visible Damage or Loose Wiring', 'value' => $this->yesNo($record->no_visible_damage_or_loose_wiring ?? false)],
                ['label' => 'Power Status On', 'value' => $this->yesNo($record->power_status_on ?? false)],
                ['label' => 'Placement Secure', 'value' => $this->yesNo($record->placement_secure ?? false)],
                ['label' => 'Started', 'value' => $this->formatSubmittedFieldDateTime($record->recorded_at ?? null)],
            ]);
        }

        if ($this->isChickPlacementTask($taskType)) {
            $record = DB::table('flock_batches')
                ->where('house_id', $task->house_houseid)
                ->where('pen_id', $task->pennumber)
                ->orderByDesc('id')
                ->first();

            if (!$record) {
                return $baseFields;
            }

            return array_merge($baseFields, [
                ['label' => 'Batch Code', 'value' => (string) ($record->batch_code ?? '-')],
                ['label' => 'Initial Population', 'value' => (string) ($record->initial_population ?? '-')],
                ['label' => 'Batch Status', 'value' => (string) ($record->status ?? '-')],
                ['label' => 'Started', 'value' => $this->formatSubmittedFieldDateTime($record->started_at)],
            ]);
        }

        return $baseFields;
    }

    private function yesNo($value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
    }

    private function isHatchTask(string $taskType): bool
    {
        return str_contains($taskType, 'hatch') || str_contains($taskType, 'mortality');
    }

    private function isWeightTask(string $taskType): bool
    {
        return str_contains($taskType, 'weight');
    }

    private function isFeedTask(string $taskType): bool
    {
        return str_contains($taskType, 'feed');
    }

    private function isVitaminTask(string $taskType): bool
    {
        return str_contains($taskType, 'vitamin');
    }

    private function isDisinfectionTask(string $taskType): bool
    {
        return str_contains($taskType, 'disinfection');
    }

    private function isCleaningTask(string $taskType): bool
    {
        return str_contains($taskType, 'cleaning');
    }

    private function isSensorTask(string $taskType): bool
    {
        return str_contains($taskType, 'sensor');
    }

    private function isChickPlacementTask(string $taskType): bool
    {
        return str_contains($taskType, 'chick') || str_contains($taskType, 'placement');
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

    private function formatSubmittedFieldDateTime($value): string
    {
        if (blank($value)) {
            return '-';
        }

        return Carbon::parse($value)->format('M j, Y g:i A');
    }

    private function formatSubmittedFieldDateAndTime($date, $time): string
    {
        if (blank($date) && blank($time)) {
            return '-';
        }

        if (blank($date)) {
            return (string) $time;
        }

        if (blank($time)) {
            return Carbon::parse($date)->format('M j, Y');
        }

        return Carbon::parse($date . ' ' . $time)->format('M j, Y g:i A');
    }
}