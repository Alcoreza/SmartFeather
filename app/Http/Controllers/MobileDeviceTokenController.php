<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileDeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $validated = $request->validate([
            'fcm_token' => 'required|string|max:4096',
            'platform' => 'nullable|string|max:50',
            'device_name' => 'nullable|string|max:255',
            'device_id' => 'required|string|max:100',
        ]);

        DB::transaction(function () use ($employeeId, $validated) {
            DB::table('mobile_device_tokens')
                ->where(function ($query) use ($validated) {
                    $query->where('device_id', $validated['device_id'])
                        ->orWhere('fcm_token', $validated['fcm_token']);
                })
                ->update([
                    'is_active' => DB::raw('false'),
                    'updated_at' => now(),
                ]);

            $existingToken = DB::table('mobile_device_tokens')
                ->where('fcm_token', $validated['fcm_token'])
                ->first();

            $existingDevice = DB::table('mobile_device_tokens')
                ->where('employee_id', $employeeId)
                ->where('device_id', $validated['device_id'])
                ->first();

            $existing = $existingToken ?: $existingDevice;

            if ($existing) {
                DB::table('mobile_device_tokens')
                    ->where('id', $existing->id)
                    ->update([
                        'employee_id' => $employeeId,
                        'fcm_token' => $validated['fcm_token'],
                        'platform' => $validated['platform'] ?? 'android',
                        'device_name' => $validated['device_name'] ?? null,
                        'device_id' => $validated['device_id'],
                        'is_active' => DB::raw('true'),
                        'last_used_at' => now(),
                        'updated_at' => now(),
                    ]);

                return;
            }

            DB::table('mobile_device_tokens')->insert([
                'employee_id' => $employeeId,
                'fcm_token' => $validated['fcm_token'],
                'platform' => $validated['platform'] ?? 'android',
                'device_name' => $validated['device_name'] ?? null,
                'device_id' => $validated['device_id'],
                'is_active' => DB::raw('true'),
                'last_used_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Notification token saved successfully.',
        ]);
    }

    public function deactivate(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $validated = $request->validate([
            'device_id' => 'required|string|max:100',
        ]);

        DB::table('mobile_device_tokens')
            ->where('employee_id', $employeeId)
            ->where('device_id', $validated['device_id'])
            ->update([
                'is_active' => DB::raw('false'),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification token deactivated successfully.',
        ]);
    }
}