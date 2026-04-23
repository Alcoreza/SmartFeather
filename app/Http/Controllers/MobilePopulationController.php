<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobilePopulationController extends Controller
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

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'house_id' => 'required|integer|exists:house,id',
            'pen_name' => 'required|string',
            'eggs_hatched' => 'required|integer|min:0',
            'mortality' => 'required|integer|min:0',
        ]);

        $pen = Pen::where('house_id', $validated['house_id'])
            ->where('pen_name', $validated['pen_name'])
            ->first();

        if (!$pen) {
            return response()->json([
                'message' => 'Pen not found.'
            ], 404);
        }

        $currentPopulation = (int) ($pen->population ?? 0);
        $newPopulation = max($currentPopulation - $validated['mortality'], 0);

        DB::transaction(function () use ($pen, $validated, $newPopulation) {
            DB::table('population_record')->insert([
                'pen_id' => $pen->id,
                'eggs_hatched' => $validated['eggs_hatched'],
                'mortality' => $validated['mortality'],
                'running_population' => $newPopulation,
                'recorded_at' => now(),
            ]);

            $pen->eggs_hatched = $validated['eggs_hatched'];
            $pen->mortality = $validated['mortality'];
            $pen->population = $newPopulation;
            $pen->recorded_at = now();
            $pen->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Population data submitted successfully.'
        ]);
    }
}
