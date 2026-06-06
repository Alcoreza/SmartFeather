<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class MobileNewBatchController extends Controller
{
    public function getContext(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:user,EmployeeId',
        ], [
            'employee_id.required' => 'Employee is required.',
            'employee_id.exists' => 'Employee account was not found.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'access_allowed' => false,
                'message' => $validator->errors()->first(),
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $latestEntry = DB::table('personnel_entry_logs')
                ->where('employee_id', $validated['employee_id'])
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->orderByDesc('id')
                ->first();

            if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
                return response()->json([
                    'success' => false,
                    'message' => 'Please scan IN before accessing new batch.',
                    'access_allowed' => false,
                    'house_id' => null,
                    'house_number' => null,
                    'pen_options' => [],
                ], 403);
            }

            $latestBiosecurity = DB::table('personnel_biosecurity_logs')
                ->where('personnel_entry_log_id', $latestEntry->id)
                ->orderByDesc('id')
                ->first();

            if (!$latestBiosecurity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete personnel biosecurity before accessing new batch.',
                    'access_allowed' => false,
                    'house_id' => null,
                    'house_number' => null,
                    'pen_options' => [],
                ], 403);
            }

            $house = House::find($latestBiosecurity->house_id);

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'The biosecurity house record could not be found.',
                    'access_allowed' => false,
                    'house_id' => null,
                    'house_number' => null,
                    'pen_options' => [],
                ], 422);
            }

            $pens = Pen::with('currentBatch:id,batch_code,status')
                ->where('house_id', $house->id)
                ->orderBy('id', 'asc')
                ->get(['id', 'pen_name', 'house_id', 'current_batch_id'])
                ->map(function (Pen $pen) {
                    return [
                        'id' => $pen->id,
                        'pen_name' => $pen->pen_name,
                        'current_batch_id' => $pen->current_batch_id,
                        'current_batch_code' => $pen->currentBatch?->batch_code,
                        'current_batch_status' => $pen->currentBatch?->status,
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
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'access_allowed' => false,
                'message' => $this->friendlyDatabaseMessage($exception, 'Unable to load new batch context.'),
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 500);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'access_allowed' => false,
                'message' => 'Unable to load new batch context. Please try again.',
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 500);
        }
    }

    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'task_id' => 'nullable|integer|exists:tasks,taskid',
            'batch_code' => 'required|string|max:100',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'initial_population' => 'required|integer|min:1',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ], [
            'employee_id.required' => 'Employee is required.',
            'employee_id.exists' => 'Employee account was not found.',
            'task_id.exists' => 'Assigned task was not found.',
            'batch_code.required' => 'Batch code is required.',
            'batch_code.max' => 'Batch code is too long.',
            'house_id.required' => 'House is required.',
            'house_id.exists' => 'Selected house was not found.',
            'pen_id.required' => 'Pen is required.',
            'pen_id.exists' => 'Selected pen was not found.',
            'initial_population.required' => 'Initial population is required.',
            'initial_population.integer' => 'Initial population must be a valid number.',
            'initial_population.min' => 'Initial population must be greater than zero.',
            'date.required' => 'Date is required.',
            'date.date' => 'Date format is invalid.',
            'time.required' => 'Time is required.',
            'time.date_format' => 'Time format is invalid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'error_code' => 'VALIDATION_FAILED',
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $house = House::where('id', $validated['house_id'])
                ->whereNull('archived_at')
                ->first();

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected house was not found or has been archived.',
                    'error_code' => 'HOUSE_NOT_AVAILABLE',
                ], 422);
            }

            if (!empty($validated['task_id'])) {
                $task = DB::table('tasks')
                    ->where('taskid', $validated['task_id'])
                    ->where('user_employeeid', $validated['employee_id'])
                    ->first();

                if (!$task) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This task is not assigned to the current employee.',
                        'error_code' => 'TASK_NOT_ASSIGNED',
                    ], 404);
                }

                if ((string) $task->status !== 'Pending') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only pending chick placement tasks can be submitted.',
                        'error_code' => 'TASK_NOT_PENDING',
                    ], 422);
                }

                $normalizedTaskType = strtolower(trim((string) $task->tasktype));
                if ($normalizedTaskType !== 'chick placement') {
                    return response()->json([
                        'success' => false,
                        'message' => 'This task is not a chick placement task.',
                        'error_code' => 'INVALID_TASK_TYPE',
                    ], 422);
                }

                if ((int) $task->house_houseid !== (int) $validated['house_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected house does not match the assigned task house.',
                        'error_code' => 'HOUSE_MISMATCH',
                    ], 403);
                }

                if ((int) $task->pennumber !== (int) $validated['pen_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected pen does not match the assigned task pen.',
                        'error_code' => 'PEN_MISMATCH',
                    ], 403);
                }
            }

            $latestEntry = DB::table('personnel_entry_logs')
                ->where('employee_id', $validated['employee_id'])
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->orderByDesc('id')
                ->first();

            if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
                return response()->json([
                    'success' => false,
                    'message' => 'Please scan IN before recording chick placement.',
                    'error_code' => 'NOT_SCANNED_IN',
                ], 403);
            }

            $biosecurityQuery = DB::table('personnel_biosecurity_logs')
                ->where('employee_id', $validated['employee_id'])
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
                    'success' => false,
                    'message' => !empty($validated['task_id'])
                        ? 'Please complete biosecurity for this assigned task before submitting chick placement.'
                        : 'Please complete personnel biosecurity before recording a new batch.',
                    'error_code' => 'BIOSECURITY_REQUIRED',
                ], 403);
            }

            $pen = Pen::where('id', $validated['pen_id'])
                ->where('house_id', $validated['house_id'])
                ->whereNull('archived_at')
                ->first();

            if (!$pen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected pen does not belong to the selected house or has been archived.',
                    'error_code' => 'PEN_HOUSE_MISMATCH',
                ], 422);
            }

            $initialPopulation = (int) $validated['initial_population'];
            $penCapacity = $pen->capacity !== null ? (int) $pen->capacity : null;

            if ($penCapacity !== null && $penCapacity > 0 && $initialPopulation > $penCapacity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Initial population cannot be greater than the selected pen capacity of ' . $penCapacity . '.',
                    'error_code' => 'POPULATION_OVER_CAPACITY',
                ], 422);
            }

            if ((int) ($pen->population ?? 0) > 0 && empty($pen->current_batch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This pen already has an existing population. Clear or end the current flock record before placing new chicks.',
                    'error_code' => 'PEN_HAS_POPULATION',
                ], 409);
            }

            $startedAt = Carbon::parse($validated['date'] . ' ' . $validated['time']);

            $existingBatch = DB::table('flock_batches')
                ->where('pen_id', $validated['pen_id'])
                ->where('status', 'Running')
                ->first();

            if (!$existingBatch && !empty($pen->current_batch_id)) {
                $existingBatch = DB::table('flock_batches')
                    ->where('id', $pen->current_batch_id)
                    ->first();
            }

            if ($existingBatch || !empty($pen->current_batch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This pen already has a running batch. End the current batch before placing new chicks.',
                    'error_code' => 'RUNNING_BATCH_EXISTS',
                    'existing_batch' => [
                        'batch_code' => $existingBatch->batch_code ?? null,
                        'started_at' => $existingBatch->started_at ?? null,
                    ],
                ], 409);
            }

            return DB::transaction(function () use ($validated, $startedAt, $initialPopulation) {
                $batchId = DB::table('flock_batches')->insertGetId([
                    'task_id' => $validated['task_id'] ?? null,
                    'batch_code' => $validated['batch_code'],
                    'house_id' => $validated['house_id'],
                    'pen_id' => $validated['pen_id'],
                    'started_at' => $startedAt,
                    'status' => 'Running',
                    'initial_population' => $initialPopulation,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('pen')
                    ->where('id', $validated['pen_id'])
                    ->update([
                        'population' => $initialPopulation,
                        'current_batch_id' => $batchId,
                        'batch_started_at' => $startedAt,
                        'eggs_hatched' => 0,
                        'mortality' => 0,
                        'recorded_at' => now(),
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => !empty($validated['task_id'])
                        ? 'Chick placement submitted successfully.'
                        : 'New bird batch added successfully.',
                    'batch_code' => $validated['batch_code'],
                    'pen_id' => $validated['pen_id'],
                    'initial_population' => $initialPopulation,
                    'started_at' => $startedAt->toDateTimeString(),
                    'status' => 'Running',
                ]);
            });
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => $this->friendlyDatabaseMessage($exception, 'Unable to submit new batch.'),
                'error_code' => 'DATABASE_ERROR',
            ], 500);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to submit new batch. Please try again.',
                'error_code' => 'SERVER_ERROR',
            ], 500);
        }
    }

    private function friendlyDatabaseMessage(QueryException $exception, string $fallback): string
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;
        $message = strtolower($exception->getMessage());

        if ($sqlState === '23505') {
            return 'This pen already has a running batch.';
        }

        if ($sqlState === '23503') {
            return 'A related house, pen, task, or employee record was not found.';
        }

        if ($sqlState === '23502') {
            return 'A required database field is missing.';
        }

        if ($sqlState === '22007' || $sqlState === '22008') {
            return 'The submitted date or time is invalid.';
        }

        if ($sqlState === '42703') {
            if (str_contains($message, 'updated_at') && str_contains($message, 'pen')) {
                return 'The pen table does not have an updated_at column. Remove updated_at from the pen update query.';
            }

            return 'A required database column is missing. Please check the new batch table setup.';
        }

        if ($driverCode === 7 && str_contains($message, 'flock_batches_one_running_per_pen')) {
            return 'This pen already has a running batch.';
        }

        return $fallback . ' Please check the database setup and try again.';
    }
}