<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileFeedsRefillController extends Controller
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

    public function getFeedInventoryOptions()
    {
        $items = DB::table('inventories')
            ->where('type', 'feed')
            ->orderBy('item_name')
            ->get([
                'id',
                'item_name',
                'remaining_stock',
                'unit',
            ])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'remaining_stock' => (int) $item->remaining_stock,
                    'unit' => $item->unit ?: 'kg',
                ];
            })
            ->values();

        return response()->json($items);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|integer|exists:inventories,id',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'feeder_number' => 'required|integer|min:1',
            'kilograms' => 'required|integer|min:1',
            'recorded_at' => 'required|date',
        ]);

        $inventory = DB::table('inventories')
            ->where('id', $validated['inventory_id'])
            ->where('type', 'feed')
            ->first();

        if (!$inventory) {
            return response()->json([
                'message' => 'Selected feed inventory was not found.',
            ], 404);
        }

        $pen = Pen::where('id', $validated['pen_id'])
            ->where('house_id', $validated['house_id'])
            ->first();

        if (!$pen) {
            return response()->json([
                'message' => 'Selected pen does not belong to the selected house.',
            ], 422);
        }

        $remainingStock = (int) $inventory->remaining_stock;
        $initialStock = (int) $inventory->initial_stock;
        $kilograms = (int) $validated['kilograms'];

        if ($remainingStock < $kilograms) {
            return response()->json([
                'message' => 'Not enough remaining feed stock.',
            ], 422);
        }

        $newRemaining = $remainingStock - $kilograms;

        DB::transaction(function () use ($validated, $initialStock, $newRemaining) {
            DB::table('feed_refill_records')->insert([
                'inventory_id' => $validated['inventory_id'],
                'house_id' => $validated['house_id'],
                'pen_id' => $validated['pen_id'],
                'feeder_number' => $validated['feeder_number'],
                'kilograms_used' => $validated['kilograms'],
                'recorded_at' => $validated['recorded_at'],
            ]);

            DB::table('inventories')
                ->where('id', $validated['inventory_id'])
                ->update([
                    'remaining_stock' => $newRemaining,
                    'updated_at' => now(),
                ]);

            DB::table('inventory_records')->insert([
                'inventory_id' => $validated['inventory_id'],
                'initial_stock' => $initialStock,
                'remaining_stock' => $newRemaining,
                'monitoring_date' => $validated['recorded_at'],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Feeds refill submitted successfully.',
        ]);
    }
}
