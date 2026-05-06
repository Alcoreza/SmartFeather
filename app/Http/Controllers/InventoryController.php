<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function managerIndex()
    {
        $items = DB::table('inventories')->get();

        $feedItems = $items->where('type', 'feed')
            ->map(fn($item) => $this->formatItem($item));

        $vitaminItems = $items->where('type', 'vitamin')
            ->map(fn($item) => $this->formatItem($item));

        return view('manager.inventory', compact('feedItems', 'vitaminItems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'type' => 'required|in:feed,vitamin',
            'unit' => 'nullable|string|max:50',
            'initial_stock' => 'required|numeric|min:0',
            'remaining_stock' => 'required|numeric|min:0',
            'critical' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
        ]);

        $unit = $validated['type'] === 'vitamin'
            ? 'bottle'
            : ($validated['unit'] ?? 'kg');

        $id = DB::table('inventories')->insertGetId([
            'item_name' => $validated['item_name'],
            'type' => $validated['type'],
            'unit' => $unit,
            'initial_stock' => $validated['initial_stock'],
            'remaining_stock' => $validated['remaining_stock'],
            'critical' => $validated['critical'],
            'purchase_date' => $validated['purchase_date'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_records')->insert([
            'inventory_id' => $id,
            'initial_stock' => $validated['initial_stock'],
            'remaining_stock' => $validated['remaining_stock'],
            'monitoring_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = DB::table('inventories')->where('id', $id)->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found.',
            ], 404);
        }

        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'initial_stock' => 'required|numeric|min:0',
            'remaining_stock' => 'required|numeric|min:0',
            'critical' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
        ]);

        $unit = $item->type === 'vitamin' ? 'bottle' : $item->unit;

        DB::table('inventories')->where('id', $id)->update([
            'item_name' => $validated['item_name'],
            'unit' => $unit,
            'initial_stock' => $validated['initial_stock'],
            'remaining_stock' => $validated['remaining_stock'],
            'critical' => $validated['critical'],
            'purchase_date' => $validated['purchase_date'],
            'updated_at' => now(),
        ]);

        DB::table('inventory_records')->insert([
            'inventory_id' => $id,
            'initial_stock' => $validated['initial_stock'],
            'remaining_stock' => $validated['remaining_stock'],
            'monitoring_date' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        DB::table('inventories')->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    public function records(Request $request)
    {
        $type = $request->type;
        $itemName = $request->item_name;

        if ($type === 'feed') {
            $query = DB::table('feed_refill_records as f')
                ->join('inventories as i', 'f.inventory_id', '=', 'i.id')
                ->leftJoin('inventory_records as r', function($join) {
                    $join->on('r.inventory_id', '=', 'f.inventory_id')
                         ->whereRaw('DATE(r.monitoring_date) = DATE(f.recorded_at)');
                })
                ->select(
                    'f.id',
                    'i.item_name',
                    'i.type',
                    'f.kilograms_used as deducted',
                    'f.recorded_at as monitoring_date',
                    'r.remaining_stock'
                )
                ->orderBy('f.recorded_at', 'desc');

            if ($itemName) {
                $query->where('i.item_name', $itemName);
            }

            $records = $query->get();
            foreach ($records as $row) {
                $row->initial_stock = null;
            }
            return response()->json($records);
        } elseif ($type === 'vitamin') {
            $query = DB::table('vitamin_refill_records as v')
                ->join('inventories as i', 'v.inventory_id', '=', 'i.id')
                ->leftJoin('inventory_records as r', function($join) {
                    $join->on('r.inventory_id', '=', 'v.inventory_id')
                         ->whereRaw('DATE(r.monitoring_date) = DATE(v.recorded_at)');
                })
                ->select(
                    'v.id',
                    'i.item_name',
                    'i.type',
                    'v.bottles_used as deducted',
                    'v.recorded_at as monitoring_date',
                    'r.remaining_stock'
                )
                ->orderBy('v.recorded_at', 'desc');

            if ($itemName) {
                $query->where('i.item_name', $itemName);
            }

            $records = $query->get();
            foreach ($records as $row) {
                $row->initial_stock = null;
            }
            return response()->json($records);
        } else {
            // fallback: return inventory_records as before, but no deducted
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

            if ($itemName) {
                $query->where('i.item_name', $itemName);
            }

            $records = $query->get();
            foreach ($records as $row) {
                $row->deducted = null;
            }
            return response()->json($records);
        }
    }

    private function formatItem($item)
    {
        $initialStock = (float) $item->initial_stock;
        $remainingStock = (float) $item->remaining_stock;
        $critical = (float) ($item->critical ?? 0);

        $percentage = $initialStock > 0
            ? round(($remainingStock / $initialStock) * 100)
            : 0;

        if ($remainingStock <= $critical) {
            $status = ['Critical', 'critical'];
        } elseif ($percentage >= 70) {
            $status = ['High', 'high'];
        } else {
            $status = ['Moderate', 'moderate'];
        }

        return [
            'id' => $item->id,
            'item_name' => $item->item_name,
            'initial_stock' => $item->initial_stock,
            'remaining_stock' => $item->remaining_stock,
            'critical' => $item->critical ?? 0,
            'purchase_date' => $item->purchase_date,
            'unit' => $item->type === 'vitamin' ? 'bottle' : $item->unit,
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

