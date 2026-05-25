<?php

namespace App\Http\Controllers;

use App\Models\Management;
use Illuminate\Http\Request;
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
}
