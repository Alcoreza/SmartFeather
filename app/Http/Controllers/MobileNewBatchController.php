<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileNewBatchController extends Controller
{
    public function getContext(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
        ]);

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before accessing add new batch.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 403);
        }

        $hasBiosecuritySubmission = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->exists();

        if (!$hasBiosecuritySubmission) {
            return response()->json([
                'message' => 'Please submit the personnel biosecurity form first before accessing add new batch.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 403);
        }

        $house = House::find($latestEntry->house_id);

        if (!$house) {
            return response()->json([
                'message' => 'Assigned house from personnel entry was not found.',
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
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'batch_code' => 'required|string|max:100',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'initial_population' => 'required|integer|min:1',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ]);

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before recording a new batch.'
            ], 403);
        }

        $hasBiosecuritySubmission = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->exists();

        if (!$hasBiosecuritySubmission) {
            return response()->json([
                'message' => 'Please submit the personnel biosecurity form first before recording a new batch.'
            ], 403);
        }

        if ((int) $latestEntry->house_id !== (int) $validated['house_id']) {
            return response()->json([
                'message' => 'You can only record a new batch for the house assigned by your latest personnel entry scan.'
            ], 403);
        }

        $pen = Pen::where('id', $validated['pen_id'])
            ->where('house_id', $validated['house_id'])
            ->first();

        if (!$pen) {
            return response()->json([
                'message' => 'Selected pen does not belong to the selected house.'
            ], 422);
        }

        $existingBatch = DB::table('flock_batches')
            ->where('pen_id', $validated['pen_id'])
            ->where('status', 'Running')
            ->first();

        if ($existingBatch || !empty($pen->current_batch_id)) {
            return response()->json([
                'message' => 'There is already a running batch in the selected pen.',
                'existing_batch' => [
                    'batch_code' => $existingBatch->batch_code ?? null,
                    'started_at' => $existingBatch->started_at ?? null,
                ],
            ], 409);
        }

        $startedAt = Carbon::parse($validated['date'] . ' ' . $validated['time']);
        $batchId = null;

        try {
            DB::transaction(function () use ($validated, $startedAt, &$batchId) {
                $batchId = DB::table('flock_batches')->insertGetId([
                    'batch_code' => $validated['batch_code'],
                    'house_id' => $validated['house_id'],
                    'pen_id' => $validated['pen_id'],
                    'started_at' => $startedAt,
                    'status' => 'Running',
                    'initial_population' => $validated['initial_population'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('pen')
                    ->where('id', $validated['pen_id'])
                    ->update([
                        'population' => $validated['initial_population'],
                        'current_batch_id' => $batchId,
                        'batch_started_at' => $startedAt,
                        'recorded_at' => $startedAt,
                    ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'New batch added successfully.',
                'batch' => [
                    'id' => $batchId,
                    'batch_code' => $validated['batch_code'],
                    'house_id' => $validated['house_id'],
                    'pen_id' => $validated['pen_id'],
                    'initial_population' => $validated['initial_population'],
                    'started_at' => $startedAt->toDateTimeString(),
                    'status' => 'Running',
                ],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23505') {
                return response()->json([
                    'message' => 'There is already a running batch in the selected pen.',
                ], 409);
            }

            throw $e;
        }
    }
}