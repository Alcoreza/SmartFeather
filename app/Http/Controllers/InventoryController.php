<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function managerIndex()
    {
        $items = DB::table('inventories')
            ->whereNull('archived_at')
            ->orderBy('id', 'asc')
            ->get();

        $feedItems = $items->where('type', 'feed')
            ->map(fn ($item) => $this->formatItem($item));

        $vitaminItems = $items->where('type', 'vitamin')
            ->map(fn ($item) => $this->formatItem($item));

        $feedTypeOptions = DB::table('inventory_types')
            ->where('category', 'feed')
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        $vitaminTypeOptions = DB::table('inventory_types')
            ->where('category', 'vitamin')
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        return view('manager.inventory', compact(
            'feedItems',
            'vitaminItems',
            'feedTypeOptions',
            'vitaminTypeOptions'
        ));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_name' => 'required|string|max:255',
                'type' => 'required|in:feed,vitamin',
                'stock_to_add' => 'required|numeric|min:0',
                'purchase_date' => 'required|date',
            ]);

            $itemName = trim($validated['item_name']);
            $itemType = $validated['type'];

            $unit = $itemType === 'vitamin' ? 'bottle' : 'kg';
            $stockToAdd = (float) $validated['stock_to_add'];

            $inventoryType = DB::table('inventory_types')
                ->where('category', $itemType)
                ->whereRaw('LOWER(name) = ?', [strtolower($itemName)])
                ->whereNull('archived_at')
                ->first();

            $existingInventory = DB::table('inventories')
                ->whereRaw('LOWER(item_name) = ?', [strtolower($itemName)])
                ->where('type', $itemType)
                ->whereNull('archived_at')
                ->first();

            if (!$inventoryType && !$existingInventory) {
                return response()->json([
                    'error' => 'Inventory type not found or already archived.',
                ], 404);
            }

            if (!$inventoryType && $existingInventory) {
                $inventoryType = (object) [
                    'initial_stock' => $existingInventory->initial_stock,
                    'critical' => $existingInventory->critical,
                ];
            }

            if ($existingInventory) {
                $newRemainingStock = (float) $existingInventory->remaining_stock + $stockToAdd;

                DB::table('inventories')
                    ->where('id', $existingInventory->id)
                    ->update([
                        'unit' => $unit,
                        'remaining_stock' => $newRemainingStock,
                        'purchase_date' => $validated['purchase_date'],
                        'updated_at' => now(),
                    ]);

                DB::table('inventory_records')->insert([
                    'inventory_id' => $existingInventory->id,
                    'initial_stock' => $existingInventory->initial_stock,
                    'remaining_stock' => $newRemainingStock,
                    'deducted' => 0,
                    'monitoring_date' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Stock added successfully.',
                ]);
            }

            $inventoryId = DB::table('inventories')->insertGetId([
                'item_name' => $itemName,
                'type' => $itemType,
                'unit' => $unit,
                'initial_stock' => $inventoryType->initial_stock,
                'remaining_stock' => $stockToAdd,
                'critical' => $inventoryType->critical,
                'purchase_date' => $validated['purchase_date'],
                'archived_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inventory_records')->insert([
                'inventory_id' => $inventoryId,
                'initial_stock' => $inventoryType->initial_stock,
                'remaining_stock' => $stockToAdd,
                'deducted' => 0,
                'monitoring_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inventory added successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'stock_to_reduce' => 'required|numeric|min:0',
            ]);

            $inventory = DB::table('inventories')
                ->where('id', $id)
                ->whereNull('archived_at')
                ->first();

            if (!$inventory) {
                return response()->json([
                    'error' => 'Inventory not found.',
                ], 404);
            }

            $stockToReduce = (float) $validated['stock_to_reduce'];
            $newRemainingStock = (float) $inventory->remaining_stock - $stockToReduce;

            if ($newRemainingStock < 0) {
                $stockToReduce = (float) $inventory->remaining_stock;
                $newRemainingStock = 0;
            }

            DB::table('inventories')
                ->where('id', $id)
                ->update([
                    'remaining_stock' => $newRemainingStock,
                    'updated_at' => now(),
                ]);

            DB::table('inventory_records')->insert([
                'inventory_id' => $id,
                'initial_stock' => $inventory->initial_stock,
                'remaining_stock' => $newRemainingStock,
                'deducted' => $stockToReduce,
                'monitoring_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock reduced successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $inventory = DB::table('inventories')
                ->where('id', $id)
                ->whereNull('archived_at')
                ->first();

            if (!$inventory) {
                DB::rollBack();

                return response()->json([
                    'error' => 'Inventory not found or already archived.',
                ], 404);
            }

            DB::table('inventories')
                ->where('id', $id)
                ->update([
                    'archived_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('inventory_types')
                ->where('category', $inventory->type)
                ->where('name', $inventory->item_name)
                ->whereNull('archived_at')
                ->update([
                    'archived_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory archived successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function records(Request $request)
    {
        $type = $request->type;
        $itemName = $request->item_name;

        $query = DB::table('inventory_records as r')
            ->join('inventories as i', 'r.inventory_id', '=', 'i.id')
            ->whereNull('i.archived_at')
            ->select(
                'r.id',
                'i.item_name',
                'i.type',
                'r.initial_stock',
                'r.remaining_stock',
                'r.deducted',
                'r.monitoring_date'
            )
            ->orderBy('r.monitoring_date', 'desc')
            ->orderBy('r.id', 'desc');

        if ($type) {
            $query->where('i.type', $type);
        }

        if ($itemName) {
            $query->where('i.item_name', $itemName);
        }

        return response()->json($query->get());
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
            ->whereNull('archived_at')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->pluck('item_name');

        return response()->json($items);
    }
}