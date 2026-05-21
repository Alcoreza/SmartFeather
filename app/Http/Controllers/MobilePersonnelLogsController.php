<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobilePersonnelLogsController extends Controller
{
    public function context(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
        ]);

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry) {
            return response()->json([
                'message' => 'No fingerprint scan found for this worker.',
            ], 404);
        }

        if (strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Worker is currently OUT. Please scan IN first before completing personnel biosecurity logs.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'employee_id' => (int) $latestEntry->employee_id,
            'personnel_entry_log_id' => (int) $latestEntry->id,
            'name' => $latestEntry->name,
            'role' => $latestEntry->role,
            'house_id' => $latestEntry->house_id ? (int) $latestEntry->house_id : null,
            'house' => $latestEntry->house,
            'status' => $latestEntry->status,
            'date' => !empty($latestEntry->date)
                ? Carbon::parse($latestEntry->date)->format('Y-m-d')
                : '',
            'time' => !empty($latestEntry->time)
                ? Carbon::parse((string) $latestEntry->time)->format('H:i:s')
                : '',
        ]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'personnel_entry_log_id' => 'required|integer|exists:personnel_entry_logs,id',
            'foot_bath' => 'required|boolean',
            'boots_changed' => 'required|boolean',
            'protective_clothing' => 'required|boolean',
        ]);

        if (!$validated['foot_bath'] || !$validated['boots_changed'] || !$validated['protective_clothing']) {
            return response()->json([
                'message' => 'All biosecurity checks must be completed before submission.',
            ], 422);
        }

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('id', $validated['personnel_entry_log_id'])
            ->where('employee_id', $validated['employee_id'])
            ->first();

        if (!$latestEntry) {
            return response()->json([
                'message' => 'Personnel entry log not found for this worker.',
            ], 404);
        }

        if (strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Worker is currently OUT. Please scan IN first before submitting personnel biosecurity logs.',
            ], 403);
        }

        $alreadySubmitted = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->exists();

        if ($alreadySubmitted) {
            return response()->json([
                'success' => true,
                'message' => 'Personnel biosecurity log already submitted for this scan.',
            ]);
        }

        DB::table('personnel_biosecurity_logs')->insert([
            'employee_id' => $validated['employee_id'],
            'personnel_entry_log_id' => $latestEntry->id,
            'name' => $latestEntry->name,
            'role' => $latestEntry->role,
            'house_id' => $latestEntry->house_id,
            'house' => $latestEntry->house,
            'date' => $latestEntry->date,
            'time' => $latestEntry->time,
            'foot_bath' => 'Yes',
            'boots_changed' => 'Yes',
            'protective_clothing' => 'Yes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Personnel biosecurity log submitted successfully.',
        ]);
    }
}