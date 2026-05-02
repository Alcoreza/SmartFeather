<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
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
        $pens = Pen::where('house_id', $houseId)
            ->orderBy('id', 'asc')
            ->get(['id', 'pen_name'])
            ->map(function (Pen $pen) {
                return [
                    'id' => $pen->id,
                    'pen_name' => $pen->pen_name,
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
            'age' => 'required|integer|min:1',
            'number_of_flocks' => 'required|integer|min:1',
            'flocks_with_cases' => 'required|integer|min:0',
            'target_weight' => 'required|numeric|min:0.01',
            'weights' => 'required|array|min:1',
            'weights.*' => 'required|numeric|min:0.01',
        ]);

        if (count($validated['weights']) !== (int) $validated['number_of_flocks']) {
            return response()->json([
                'message' => 'Weight count must match number of flocks.'
            ], 422);
        }

        $pen = Pen::where('id', $validated['pen_id'])
            ->where('house_id', $validated['house_id'])
            ->first();

        if (!$pen) {
            return response()->json([
                'message' => 'Selected pen does not belong to the selected house.'
            ], 422);
        }

        $house = House::find($validated['house_id']);

        $weights = array_map('floatval', $validated['weights']);
        $average = round(array_sum($weights) / count($weights), 2);
        $targetValue = round((float) $validated['target_weight'], 2);

        $status = match (true) {
            $average < $targetValue => 'Underweight',
            $average > $targetValue => 'Overweight',
            default => 'Normal',
        };

        $now = now();

        DB::transaction(function () use ($validated, $weights, $average, $targetValue, $status, $house, $pen, $now) {
            $logId = DB::table('weight_sampling_logs')->insertGetId([
                'date' => $now->toDateString(),
                'time' => $now->format('H:i:s'),
                'house' => $house?->house_number,
                'pen' => $pen->pen_name,
                'age' => (string) $validated['age'],
                'number_of_flocks' => $validated['number_of_flocks'],
                'flocks_with_cases' => $validated['flocks_with_cases'],
                'average_weight' => (string) $average,
                'target' => (string) $targetValue,
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($weights as $index => $weight) {
                DB::table('weight_sampling_entries')->insert([
                    'weight_sampling_log_id' => $logId,
                    'sequence_number' => $index + 1,
                    'weight' => $weight,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Weight sampling submitted successfully.',
            'average_weight' => $average,
            'target' => $targetValue,
            'status' => $status,
        ]);
    }
}
