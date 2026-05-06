<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileNewBatchController extends Controller
{
    public function getHouses()
    {
        $houses = House::orderBy('id', 'asc')
            ->get(['id', 'house_number', 'number_of_pens'])
            ->map(function (House $house) {
                return [
                    'id' => $house->id,
                    'house_number' => $house->house_number,
                    'number_of_pens' => $house->number_of_pens,
                ];
            })
            ->values();

        return response()->json($houses);
    }

    public function getPensByHouse($houseId)
    {
        $pens = Pen::with('currentBatch:id,batch_code')
            ->where('house_id', $houseId)
            ->orderBy('id', 'asc')
            ->get(['id', 'pen_name', 'house_id', 'current_batch_id'])
            ->map(function (Pen $pen) {
                return [
                    'id' => $pen->id,
                    'pen_name' => $pen->pen_name,
                    'current_batch_id' => $pen->current_batch_id,
                    'current_batch_code' => $pen->currentBatch?->batch_code,
                ];
            })
            ->values();

        return response()->json($pens);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'batch_code' => 'required|string|max:100',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'initial_population' => 'required|integer|min:1',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ]);

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
                        'recorded_at' => now(),
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
