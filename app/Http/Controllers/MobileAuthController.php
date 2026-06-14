<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'device_id' => 'required|string|max:100',
            'device_name' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:50',
        ]);

        $user = User::where('Username', $validated['username'])->first();

        $storedPassword = $user?->Password;
        $normalizedHash = $storedPassword;

        if ($normalizedHash && str_starts_with($normalizedHash, '$2a$')) {
            $normalizedHash = '$2y$' . substr($normalizedHash, 4);
        }

        $passwordMatches = $normalizedHash
            ? password_verify($validated['password'], $normalizedHash)
            : false;

        \Log::info('mobile login debug', [
            'username' => $validated['username'],
            'user_found' => (bool) $user,
            'role' => $user?->Role,
            'stored_prefix' => $storedPassword ? substr($storedPassword, 0, 4) : null,
            'normalized_prefix' => $normalizedHash ? substr($normalizedHash, 0, 4) : null,
            'password_matches' => $passwordMatches,
        ]);

        if (!$user || !$user->is_active || !$normalizedHash || !$passwordMatches) {
            return response()->json([
                'message' => 'Invalid username or password.',
            ], 401);
        }

        if (strtolower((string) $user->Role) !== 'flockman') {
            return response()->json([
                'message' => 'Only Flockman accounts can sign in on mobile.',
            ], 403);
        }

        DB::table('mobile_api_tokens')
            ->where('employee_id', $user->EmployeeId)
            ->where('device_id', $validated['device_id'])
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);

        $plainToken = Str::random(80);

        DB::table('mobile_api_tokens')->insert([
            'employee_id' => $user->EmployeeId,
            'token_hash' => hash('sha256', $plainToken),
            'name' => 'android',
            'device_id' => $validated['device_id'],
            'device_name' => $validated['device_name'] ?? null,
            'platform' => $validated['platform'] ?? 'android',
            'last_used_at' => now(),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_in_days' => 30,
            'user' => [
                'EmployeeId' => $user->EmployeeId,
                'FirstName' => $user->FirstName,
                'LastName' => $user->LastName,
                'Role' => $user->Role,
                'Username' => $user->Username,
            ],
        ]);
    }
}
