<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\House;
use App\Models\Pen;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
    public function index()
    {
        return response()->json(Cache::remember('manager_tasks_index', now()->addSeconds(15), function () {
        $tasks = Task::with(['employee', 'house', 'pen'])
            ->orderBy('timeassigned', 'desc')
            ->get()
            ->map(function (Task $task) {
                $employee = $task->employee;
                $house = $task->house;
                $pen = $task->pen;
                $fullName = trim(sprintf(
                    '%s %s %s %s',
                    $employee->FirstName ?? '',
                    $employee->MiddleName ?? '',
                    $employee->LastName ?? '',
                    $employee->Suffix ?? '',
                ));

                $formatDate = function ($value) {
                    return $value ? Carbon::parse($value)->format('Y-m-d H:i') : '';
                };

                $status = strtolower(trim($task->status ?? 'pending'));
                if ($status === 'for approval' || $status === 'submitted') {
                    $status = 'for_approval';
                }

                return [
                    'id' => $task->taskid,
                    'name' => $fullName ?: 'Unknown',
                    'task_assigned' => $task->tasktype,
                    'house_number' => $house?->house_number ?? null,
                    'house_id' => $house?->id ?? null,
                    'pen_number' => $pen?->pen_name ?? 'Unknown Pen',
                    'pen_id' => $pen?->id ?? null,
                    'detailed_task' => $task->detailedtask ?? '',
                    'priority' => $task->prioritylevel,
                    'time_assigned' => $formatDate($task->timeassigned),
                    'finish_by' => $formatDate($task->finishby),
                    'photo_name' => $task->photourl ? basename($task->photourl) : '',
                    'photo_url' => $this->buildTaskPhotoUrl($task->photourl),
                    'notes' => $task->notes ?? '',
                    'time_completed' => $formatDate($task->time_completed),
                    'status' => $status,
                ];
            });

        return [
            'pending' => $tasks->where('status', 'pending')->values(),
            'for_approval' => $tasks->where('status', 'for_approval')->values(),
            'completed' => $tasks->where('status', 'completed')->values(),
        ];
        }));
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
            ltrim($path, '/'),
        );
    }

    private function isChickPlacementTask(?string $taskType): bool
    {
        return strtolower(trim((string) $taskType)) === 'chick placement';
    }

    private function validateAssignablePen(int $houseId, int $penId, ?string $taskType): ?\Illuminate\Http\JsonResponse
    {
        $pen = Pen::where('id', $penId)
            ->where('house_id', $houseId)
            ->whereNull('archived_at')
            ->first();

        if (! $pen) {
            return response()->json([
                'errors' => [
                    'pennumber' => ['Select a valid pen in the selected house.'],
                ],
            ], 422);
        }

        if (! $this->isChickPlacementTask($taskType) && ! $pen->runningBatch()->exists()) {
            return response()->json([
                'errors' => [
                    'pennumber' => ['Select a pen with a running batch.'],
                ],
            ], 422);
        }

        return null;
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_employeeid' => 'required|integer|exists:user,EmployeeId',
                'tasktype' => 'required|string|max:255',
                'prioritylevel' => 'required|string|max:255',
                'house_houseid' => 'required|integer|exists:house,id',
                'pennumber' => 'required|integer',
                'timeassigned' => 'nullable|date_format:Y-m-d\TH:i:s',
                'finishby' => 'nullable|date_format:Y-m-d\TH:i:s',
                'detailedtask' => 'nullable|string',
                'status' => 'required|string|in:Pending,For Approval,Completed',
            ]);

            $validated['timeassigned'] = now()->format('Y-m-d\TH:i:s');

            if ($this->finishDateIsPast($validated['finishby'] ?? null)) {
                return response()->json([
                    'errors' => [
                        'finishby' => ['Date to finish cannot be earlier than today.'],
                    ],
                ], 422);
            }

            // Ensure the chosen task type exists in the task_type table.
            $taskTypeName = trim($validated['tasktype']);
            if ($taskTypeName !== '') {
                $exists = DB::table('task_type')
                    ->where('task', $taskTypeName)
                    ->exists();

                if (! $exists) {
                    DB::table('task_type')->insert([
                        'task' => $taskTypeName,
                    ]);
                }
            }

            $penError = $this->validateAssignablePen(
                (int) $validated['house_houseid'],
                (int) $validated['pennumber'],
                $validated['tasktype'],
            );

            if ($penError) {
                return $penError;
            }

            $task = Task::create($validated);
            $this->clearTaskCaches();

            return response()->json($task, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Task validation error:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Task creation error:', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $taskId)
    {
        try {
            $validated = $request->validate([
                'status' => 'nullable|string|in:Pending,For Approval,Completed',
                'tasktype' => 'nullable|string|max:255',
                'prioritylevel' => 'nullable|string|max:255',
                'house_houseid' => 'nullable|integer|exists:house,id',
                'pennumber' => 'nullable|integer',
                'finishby' => 'nullable|date_format:Y-m-d H:i:s',
                'detailedtask' => 'nullable|string',
            ]);

            $task = Task::findOrFail($taskId);

            if ($this->finishDateIsPast($validated['finishby'] ?? null)) {
                return response()->json([
                    'errors' => [
                        'finishby' => ['Date to finish cannot be earlier than today.'],
                    ],
                ], 422);
            }

            if (array_key_exists('tasktype', $validated)
                || array_key_exists('house_houseid', $validated)
                || array_key_exists('pennumber', $validated)
            ) {
                $nextTaskType = $validated['tasktype'] ?? $task->tasktype;
                $nextHouseId = (int) ($validated['house_houseid'] ?? $task->house_houseid);
                $nextPenId = (int) ($validated['pennumber'] ?? $task->pennumber);

                $penError = $this->validateAssignablePen($nextHouseId, $nextPenId, $nextTaskType);

                if ($penError) {
                    return $penError;
                }
            }

            // Remove null values to only update provided fields
            $updateData = array_filter($validated, function($value) {
                return $value !== null;
            });

            $task->update($updateData);
            $this->clearTaskCaches();

            return response()->json($task, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Task update validation error:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Task update error:', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($taskId)
    {
        try {
            $task = Task::findOrFail($taskId);
            $task->delete();
            $this->clearTaskCaches();

            return response()->json(['message' => 'Task deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Task deletion error:', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function formOptions()
    {
        return response()->json(Cache::remember('manager_tasks_form_options', now()->addSeconds(15), function () {
        $pendingWorkerIds = Task::where('status', 'Pending')
            ->pluck('user_employeeid')
            ->unique()
            ->toArray();

        $workers = Employee::where('Role', 'Flockman')
            ->whereRaw('is_active is true')
            ->get()
            ->map(function (Employee $employee) use ($pendingWorkerIds) {
                $fullName = trim(sprintf(
                    '%s %s %s %s',
                    $employee->FirstName ?? '',
                    $employee->MiddleName ?? '',
                    $employee->LastName ?? '',
                    $employee->Suffix ?? '',
                ));

                return [
                    'id' => $employee->EmployeeId,
                    'name' => $fullName ?: 'Unknown',
                    'disabled' => in_array($employee->EmployeeId, $pendingWorkerIds, true),
                ];
            });

        $houses = House::query()
            ->whereNull('archived_at')
            ->orderBy('house_number', 'asc')
            ->get()
            ->map(function (House $house) {
                return [
                    'id' => $house->id,
                    'number' => $house->house_number,
                ];
            });

        $taskCategories = DB::table('task_type')
            ->orderBy('id', 'asc')
            ->pluck('task')
            ->map(function($task) {
                return trim($task);
            })
            ->toArray();

        return [
            'workers' => $workers,
            'houses' => $houses,
            'pens' => [],
            'task_categories' => $taskCategories,
            'priority_levels' => ['Low', 'Medium', 'High'],
        ];
        }));
    }

    private function finishDateIsPast(?string $finishBy): bool
    {
        if (blank($finishBy)) {
            return false;
        }

        return Carbon::parse($finishBy)->toDateString() < now()->toDateString();
    }

    public function getPensForHouse(Request $request, $houseId)
    {
        $allowWithoutRunningBatch = $this->isChickPlacementTask($request->query('task_type'));
        $cacheKey = 'manager_tasks_pens_for_house:' . $houseId . ':' . ($allowWithoutRunningBatch ? 'all' : 'running');

        $pens = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($houseId, $allowWithoutRunningBatch) {
            return Pen::where('house_id', $houseId)
            ->whereNull('archived_at')
            ->orderBy('id', 'asc')
            ->get(['id', 'pen_name'])
            ->map(function (Pen $pen) use ($allowWithoutRunningBatch) {
                // Check if pen has a running batch
                $hasRunningBatch = $pen->runningBatch()->exists();
                
                return [
                    'number' => $pen->id,
                    'label' => $pen->pen_name,
                    'disabled' => !$allowWithoutRunningBatch && !$hasRunningBatch,
                    'disabledReason' => !$allowWithoutRunningBatch && !$hasRunningBatch ? 'no running batch' : null,
                ];
            });
        });

        return response()->json(['pens' => $pens]);
    }

    /**
     * Get all workers (employees) without role filtering
     */
    public function getAllWorkers()
    {
        $workers = Cache::remember('manager_tasks_all_workers', now()->addSeconds(30), function () {
            return Employee::whereRaw('is_active is true')
            ->get()
            ->map(function (Employee $employee) {
                $fullName = trim(sprintf(
                    '%s %s %s %s',
                    $employee->FirstName ?? '',
                    $employee->MiddleName ?? '',
                    $employee->LastName ?? '',
                    $employee->Suffix ?? '',
                ));

                return [
                    'id' => $employee->EmployeeId,
                    'name' => $fullName ?: 'Unknown',
                ];
            });
        });

        return response()->json(['workers' => $workers]);
    }

    private function clearTaskCaches(): void
    {
        Cache::forget('manager_tasks_index');
        Cache::forget('manager_tasks_form_options');
        Cache::forget('manager_tasks_all_workers');
    }
}
