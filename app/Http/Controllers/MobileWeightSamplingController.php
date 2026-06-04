<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileWeightSamplingController extends Controller
{
    public function getContext(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
        ]);

        $latestEntry = $this->latestEntryLog($validated['employee_id']);

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before accessing weight sampling.',
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
                'message' => 'Please submit the personnel biosecurity form first before accessing weight sampling.',
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

        $pens = Pen::with('currentBatch:id,batch_code,started_at,status')
            ->where('house_id', $house->id)
            ->orderBy('id', 'asc')
            ->get(['id', 'pen_name', 'house_id', 'current_batch_id'])
            ->map(function (Pen $pen) {
                return [
                    'id' => $pen->id,
                    'pen_name' => $pen->pen_name,
                    'current_batch_id' => $pen->current_batch_id,
                    'current_batch_code' => $pen->currentBatch?->batch_code,
                    'current_batch_started_at' => !empty($pen->currentBatch?->started_at)
                        ? Carbon::parse($pen->currentBatch->started_at)->toDateTimeString()
                        : null,
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
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'task_id' => 'nullable|integer|exists:tasks,taskid',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'number_of_flocks' => 'required|integer|min:1',
            'flocks_with_cases' => 'required|integer|min:0',
            'target_weight' => 'required|numeric|min:0.01',
            'weights' => 'required|array|min:1',
            'weights.*' => 'required|numeric|min:0.01',
            'recorded_at' => 'nullable|date',
            'recorded_date' => 'nullable|date',
            'recorded_time' => 'nullable|date_format:H:i:s',
        ]);

        if (count($validated['weights']) !== (int) $validated['number_of_flocks']) {
            return response()->json([
                'message' => 'Weight count must match number of flocks.'
            ], 422);
        }

        if ((int) $validated['flocks_with_cases'] > (int) $validated['number_of_flocks']) {
            return response()->json([
                'message' => 'Flocks with cases cannot be greater than the number of flocks sampled.'
            ], 422);
        }

        $task = null;

        if (!empty($validated['task_id'])) {
            $task = DB::table('tasks')
                ->where('taskid', $validated['task_id'])
                ->where('user_employeeid', $validated['employee_id'])
                ->first();

            if (!$task) {
                return response()->json([
                    'message' => 'Selected task was not found for this employee.'
                ], 404);
            }

            if (strtolower((string) $task->status) !== 'pending') {
                return response()->json([
                    'message' => 'This task is no longer pending.'
                ], 422);
            }

            if (!$this->isWeightMonitoringTask((string) $task->tasktype)) {
                return response()->json([
                    'message' => 'This task is not a weight monitoring task.'
                ], 422);
            }

            if ((int) $task->house_houseid !== (int) $validated['house_id']) {
                return response()->json([
                    'message' => 'Selected house does not match the assigned task house.'
                ], 403);
            }

            if ((int) $task->pennumber !== (int) $validated['pen_id']) {
                return response()->json([
                    'message' => 'Selected pen does not match the assigned task pen.',
                ], 403);
            }
        }

        $latestEntry = $this->latestEntryLog($validated['employee_id']);

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before recording weight sampling.'
            ], 403);
        }

        $biosecurityQuery = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->where('employee_id', $validated['employee_id'])
            ->where('house_id', $validated['house_id'])
            ->orderByDesc('id');

        if (!empty($validated['task_id'])) {
            $biosecurityQuery
                ->where('task_id', $validated['task_id'])
                ->where('pen_id', $validated['pen_id']);
        }

        $latestBiosecurity = $biosecurityQuery->first();

        if (!$latestBiosecurity) {
            return response()->json([
                'message' => !empty($validated['task_id'])
                    ? 'Please complete personnel biosecurity for this assigned task before recording weight monitoring.'
                    : 'Please submit the personnel biosecurity form first before recording weight sampling.'
            ], 403);
        }

        $pen = Pen::with('currentBatch')
            ->where('id', $validated['pen_id'])
            ->where('house_id', $validated['house_id'])
            ->first();

        if (!$pen) {
            return response()->json([
                'message' => 'Selected pen does not belong to the selected house.'
            ], 422);
        }

        if (!$pen->currentBatch || $pen->currentBatch->status !== 'Running') {
            return response()->json([
                'message' => 'Selected pen has no running batch.'
            ], 422);
        }

        $house = House::find($validated['house_id']);
        $recordedAt = $this->resolveRecordedAt($validated);

        if (!$recordedAt) {
            return response()->json([
                'message' => 'Please provide a valid recorded date and time.'
            ], 422);
        }

        $startedAt = Carbon::parse($pen->currentBatch->started_at);

        if ($recordedAt->lt($startedAt)) {
            return response()->json([
                'message' => 'Recorded date/time cannot be earlier than the batch start date.'
            ], 422);
        }

        $ageDays = $startedAt->copy()->startOfDay()->diffInDays($recordedAt->copy()->startOfDay());

        $weights = array_map('floatval', $validated['weights']);
        $average = round(array_sum($weights) / count($weights), 2);
        $targetValue = round((float) $validated['target_weight'], 2);

        $status = match (true) {
            $average < $targetValue => 'Underweight',
            $average > $targetValue => 'Overweight',
            default => 'Normal',
        };

        DB::transaction(function () use ($validated, $weights, $average, $targetValue, $status, $house, $pen, $recordedAt, $ageDays) {
            $logId = DB::table('weight_sampling_logs')->insertGetId([
                'task_id' => $validated['task_id'] ?? null,
                'house_id' => $validated['house_id'],
                'pen_id' => $validated['pen_id'],
                'date' => $recordedAt->toDateString(),
                'time' => $recordedAt->format('H:i:s'),
                'house' => $house?->house_number,
                'pen' => $pen->pen_name,
                'batch' => $pen->currentBatch?->batch_code,
                'batch_id' => $pen->current_batch_id,
                'age' => (string) $ageDays,
                'number_of_flocks' => $validated['number_of_flocks'],
                'flocks_with_cases' => (string) $validated['flocks_with_cases'],
                'average_weight' => (string) $average,
                'target' => (string) $targetValue,
                'status' => $status,
                'created_at' => $recordedAt,
                'updated_at' => $recordedAt,
            ]);

            foreach ($weights as $index => $weight) {
                DB::table('weight_sampling_entries')->insert([
                    'weight_sampling_log_id' => $logId,
                    'sequence_number' => $index + 1,
                    'weight' => $weight,
                    'created_at' => $recordedAt,
                    'updated_at' => $recordedAt,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Weight sampling submitted successfully.',
            'average_weight' => $average,
            'target' => $targetValue,
            'status' => $status,
            'age_days' => $ageDays,
            'batch_code' => $pen->currentBatch?->batch_code,
        ]);
    }

    private function latestEntryLog(int $employeeId)
    {
        return DB::table('personnel_entry_logs')
            ->where('employee_id', $employeeId)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();
    }

    private function resolveRecordedAt(array $validated): ?Carbon
    {
        if (!empty($validated['recorded_at'])) {
            return Carbon::parse($validated['recorded_at']);
        }

        if (!empty($validated['recorded_date']) && !empty($validated['recorded_time'])) {
            return Carbon::parse($validated['recorded_date'] . ' ' . $validated['recorded_time']);
        }

        return null;
    }

    private function isWeightMonitoringTask(string $taskType): bool
    {
        $normalized = strtolower(trim($taskType));

        return $normalized === 'weight monitoring' ||
            str_contains($normalized, 'weight') && str_contains($normalized, 'monitoring');
    }
}