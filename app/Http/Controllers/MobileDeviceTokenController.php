<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileDeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'fcm_token' => 'required|string',
            'platform' => 'nullable|string|max:50',
            'device_name' => 'nullable|string|max:255',
        ]);

        $existingToken = DB::table('mobile_device_tokens')
            ->where('fcm_token', $validated['fcm_token'])
            ->first();

        if ($existingToken) {
            DB::table('mobile_device_tokens')
                ->where('id', $existingToken->id)
                ->update([
                    'employee_id' => $validated['employee_id'],
                    'platform' => $validated['platform'] ?? 'android',
                    'device_name' => $validated['device_name'] ?? null,
                    'is_active' => DB::raw('true'),
                    'last_used_at' => now(),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('mobile_device_tokens')->insert([
                'employee_id' => $validated['employee_id'],
                'fcm_token' => $validated['fcm_token'],
                'platform' => $validated['platform'] ?? 'android',
                'device_name' => $validated['device_name'] ?? null,
                'is_active' => DB::raw('true'),
                'last_used_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification token saved successfully.',
        ]);
    }
}