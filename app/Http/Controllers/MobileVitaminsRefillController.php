<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileVitaminsRefillController extends Controller
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

    public function getVitaminInventoryOptions()
    {
        $items = DB::table('inventories')
            ->where('type', 'vitamin')
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
                    'unit' => $item->unit ?: 'bottle',
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
            'bottles' => 'required|integer|min:1',
            'recorded_at' => 'required|date',
        ]);

        $inventory = DB::table('inventories')
            ->where('id', $validated['inventory_id'])
            ->where('type', 'vitamin')
            ->first();

        if (!$inventory) {
            return response()->json([
                'message' => 'Selected vitamin inventory was not found.',
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
        $bottles = (int) $validated['bottles'];

        if ($remainingStock < $bottles) {
            return response()->json([
                'message' => 'Not enough remaining vitamin stock.',
            ], 422);
        }

        $newRemaining = $remainingStock - $bottles;

        DB::transaction(function () use ($validated, $initialStock, $newRemaining) {
            DB::table('vitamin_refill_records')->insert([
                'inventory_id' => $validated['inventory_id'],
                'house_id' => $validated['house_id'],
                'pen_id' => $validated['pen_id'],
                'bottles_used' => $validated['bottles'],
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
            'message' => 'Vitamins refill submitted successfully.',
        ]);
    }
}
