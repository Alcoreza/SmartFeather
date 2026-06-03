<?php

namespace App\Http\Controllers;

use App\Models\Management;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManagementCreateTaskController extends Controller
{
    public function storeTaskType(Request $request)
    {
        try {
            $validated = $request->validate([
                'task_type' => 'required|string|max:255',
            ]);

            $taskType = trim((string) $validated['task_type']);

            if ($taskType === '') {
                return response()->json(['error' => 'Task type is required.'], 422);
            }

            $exists = Management::query()
                ->whereRaw('LOWER(task) = ?', [strtolower($taskType)])
                ->exists();

            if ($exists) {
                return response()->json(['error' => 'Task type already exists.'], 422);
            }

            Management::create([
                'task' => $taskType,
            ]);

            return response()->json(['task' => $taskType], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Management create task type validation error:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Management create task type creation error:', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeInventoryType(Request $request)
    {
        try {
            $validated = $request->validate([
                'category' => 'required|in:feed,vitamin',
                'name' => 'required|string|max:255',
                'initial_stock' => 'required|numeric|min:0',
                'critical' => 'required|numeric|min:0',
            ]);

            $category = $validated['category'];
            $name = trim($validated['name']);
            $unit = $category === 'vitamin' ? 'bottle' : 'kg';

            $exists = DB::table('inventories')
                ->where('type', $category)
                ->whereRaw('LOWER(item_name) = ?', [strtolower($name)])
                ->whereNull('archived_at')
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Inventory item already exists.',
                ], 422);
            }

            DB::beginTransaction();

            $inventoryId = DB::table('inventories')->insertGetId([
                'item_name' => $name,
                'type' => $category,
                'unit' => $unit,
                'initial_stock' => $validated['initial_stock'],
                'remaining_stock' => $validated['initial_stock'],
                'critical' => $validated['critical'],
                'purchase_date' => now()->toDateString(),
                'archived_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inventory_records')->insert([
                'inventory_id' => $inventoryId,
                'initial_stock' => $validated['initial_stock'],
                'remaining_stock' => $validated['initial_stock'],
                'deducted' => 0,
                'added' => 0,
                'initial_purchase_date' => now()->toDateString(),
                'monitoring_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory item saved successfully.',
                'id' => $inventoryId,
                'inventory_id' => $inventoryId,
                'category' => $category,
                'name' => $name,
                'initial_stock' => $validated['initial_stock'],
                'remaining_stock' => $validated['initial_stock'],
                'critical' => $validated['critical'],
                'unit' => $unit,
                'purchase_date' => now()->toDateString(),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Create inventory item error:', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
