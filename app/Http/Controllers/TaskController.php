<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\House;
use App\Models\Pen;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
    public function index()
    {
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
                    'pen_number' => $pen?->pen_name ?? 'Unknown Pen',
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

        return response()->json([
            'pending' => $tasks->where('status', 'pending')->values(),
            'for_approval' => $tasks->where('status', 'for_approval')->values(),
            'completed' => $tasks->where('status', 'completed')->values(),
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
            ltrim($path, '/'),
        );
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

            $task = Task::create($validated);

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
                'status' => 'required|string|in:Pending,For Approval,Completed',
            ]);

            $task = Task::findOrFail($taskId);
            $task->update($validated);

            return response()->json($task, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Task update validation error:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Task update error:', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function formOptions()
    {
        $pendingWorkerIds = Task::where('status', 'Pending')
            ->pluck('user_employeeid')
            ->unique()
            ->toArray();

        $workers = Employee::where('Role', 'Flockman')
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
            ->toArray();

        return response()->json([
            'workers' => $workers,
            'houses' => $houses,
            'pens' => [],
            'task_categories' => $taskCategories,
            'priority_levels' => ['Low', 'Medium', 'High'],
        ]);
    }

    public function getPensForHouse($houseId)
    {
        $pens = Pen::where('house_id', $houseId)
            ->whereNull('archived_at')
            ->orderBy('id', 'asc')
            ->get(['id', 'pen_name'])
            ->map(function (Pen $pen) {
                // Check if pen has a running batch
                $hasRunningBatch = $pen->runningBatch()->exists();
                
                return [
                    'number' => $pen->id,
                    'label' => $pen->pen_name,
                    'disabled' => !$hasRunningBatch,
                    'disabledReason' => !$hasRunningBatch ? 'no running batch' : null,
                ];
            });

        return response()->json(['pens' => $pens]);
    }

    /**
     * Get all workers (employees) without role filtering
     */
    public function getAllWorkers()
    {
        $workers = Employee::all()
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

        return response()->json(['workers' => $workers]);
    }
}
