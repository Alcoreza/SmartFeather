<?php

namespace App\Http\Controllers;

use App\Models\Management;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
            ]);

            $category = $validated['category'];
            $name = trim($validated['name']);

            $exists = DB::table('inventory_types')
                ->where('category', $category)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Inventory type already exists.',
                ], 422);
            }

            $id = DB::table('inventory_types')->insertGetId([
                'category' => $category,
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'id' => $id,
                'category' => $category,
                'name' => $name,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create inventory type error:', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
