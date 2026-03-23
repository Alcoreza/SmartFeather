<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    // GET ALL (for manager page)
    public function managerIndex()
    {
        $items = DB::table('inventories')->get();

        $feedItems = $items->where('type', 'feed')
            ->map(fn($item) => $this->formatItem($item));

        $vitaminItems = $items->where('type', 'vitamin')
            ->map(fn($item) => $this->formatItem($item));

        return view('manager.inventory', compact('feedItems', 'vitaminItems'));
    }

    // STORE
    public function store(Request $request)
    {
        $id = DB::table('inventories')->insertGetId([
            'item_name' => $request->item_name,
            'type' => $request->type,
            'unit' => $request->unit,
            'initial_stock' => $request->initial_stock,
            'remaining_stock' => $request->remaining_stock,
            'purchase_date' => $request->purchase_date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // create record log
        DB::table('inventory_records')->insert([
            'inventory_id' => $id,
            'initial_stock' => $request->initial_stock,
            'remaining_stock' => $request->remaining_stock,
            'monitoring_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $id
        ]);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        DB::table('inventories')->where('id', $id)->update([
            'item_name' => $request->item_name,
            'initial_stock' => $request->initial_stock,
            'remaining_stock' => $request->remaining_stock,
            'purchase_date' => $request->purchase_date,
            'updated_at' => now(),
        ]);

        // log change
        DB::table('inventory_records')->insert([
            'inventory_id' => $id,
            'initial_stock' => $request->initial_stock,
            'remaining_stock' => $request->remaining_stock,
            'monitoring_date' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // DELETE
    public function destroy($id)
    {
        DB::table('inventories')->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    // ✅ NEW: GET INVENTORY RECORDS (history / logs)
    public function records(Request $request)
    {
        $type = $request->type; // feed or vitamin
        $itemName = $request->item_name;

        $query = DB::table('inventory_records as r')
            ->join('inventories as i', 'r.inventory_id', '=', 'i.id')
            ->select(
                'r.id',
                'i.item_name',
                'i.type',
                'r.initial_stock',
                'r.remaining_stock',
                'r.monitoring_date'
            )
            ->orderBy('r.monitoring_date', 'desc');

        if ($type) {
            $query->where('i.type', $type);
        }

        if ($itemName) {
            $query->where('i.item_name', $itemName);
        }

        return response()->json($query->get());
    }

    // FORMAT ITEM (for UI display)
    private function formatItem($item)
    {
        $percentage = $item->initial_stock > 0
            ? round(($item->remaining_stock / $item->initial_stock) * 100)
            : 0;

        $status = match (true) {
            $percentage >= 70 => ['High', 'high'],
            $percentage >= 30 => ['Moderate', 'moderate'],
            default => ['Critical', 'critical'],
        };

        return [
            'id' => $item->id,
            'item_name' => $item->item_name,
            'initial_stock' => $item->initial_stock,
            'remaining_stock' => $item->remaining_stock,
            'purchase_date' => $item->purchase_date,
            'unit' => $item->unit,
            'percentage' => $percentage,
            'status' => $status[0],
            'status_class' => $status[1],
        ];
    }
    public function items(Request $request)
    {
        $type = $request->type;

        $items = DB::table('inventories')
            ->when($type, fn($q) => $q->where('type', $type))
            ->pluck('item_name');

        return response()->json($items);
    }
}
