<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileWeightSamplingController extends Controller
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
        $pens = Pen::with('currentBatch:id,batch_code,started_at,status')
            ->where('house_id', $houseId)
            ->orderBy('id', 'asc')
            ->get(['id', 'pen_name', 'house_id', 'current_batch_id'])
            ->map(function (Pen $pen) {
                return [
                    'id' => $pen->id,
                    'pen_name' => $pen->pen_name,
                    'current_batch_id' => $pen->current_batch_id,
                    'current_batch_code' => $pen->currentBatch?->batch_code,
                    'current_batch_started_at' => !empty($pen->currentBatch?->started_at)
                        ? \Illuminate\Support\Carbon::parse($pen->currentBatch->started_at)->toDateTimeString()
                        : null,
                ];
            })
            ->values();

        return response()->json($pens);
    }


    public function submit(Request $request)
    {
        $validated = $request->validate([
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'number_of_flocks' => 'required|integer|min:1',
            'flocks_with_cases' => 'required|integer|min:0',
            'target_weight' => 'required|numeric|min:0.01',
            'weights' => 'required|array|min:1',
            'weights.*' => 'required|numeric|min:0.01',
            'recorded_date' => 'required|date',
            'recorded_time' => 'required|date_format:H:i:s',
        ]);

        if (count($validated['weights']) !== (int) $validated['number_of_flocks']) {
            return response()->json([
                'message' => 'Weight count must match number of flocks.'
            ], 422);
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
        $recordedAt = Carbon::parse($validated['recorded_date'] . ' ' . $validated['recorded_time']);
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
}
