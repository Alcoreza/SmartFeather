<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class MobileAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'password' => 'required|string',
        ]);

        $user = User::where('EmployeeId', $validated['user_id'])->first();

        $storedPassword = $user?->Password;
        $normalizedHash = $storedPassword;

        if ($normalizedHash && str_starts_with($normalizedHash, '$2a$')) {
            $normalizedHash = '$2y$' . substr($normalizedHash, 4);
        }

        $passwordMatches = $normalizedHash
            ? password_verify($validated['password'], $normalizedHash)
            : false;

        \Log::info('mobile login debug', [
            'employee_id' => $validated['user_id'],
            'user_found' => (bool) $user,
            'role' => $user?->Role,
            'stored_prefix' => $storedPassword ? substr($storedPassword, 0, 4) : null,
            'normalized_prefix' => $normalizedHash ? substr($normalizedHash, 0, 4) : null,
            'password_matches' => $passwordMatches,
        ]);

        if (!$user || !$normalizedHash || !$passwordMatches) {
            return response()->json([
                'message' => 'Invalid employee ID or password.'
            ], 401);
        }

        if (strtolower($user->Role) !== 'flockman') {
            return response()->json([
                'message' => 'Only Flockman accounts can sign in on mobile.'
            ], 403);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'EmployeeId' => $user->EmployeeId,
                'FirstName' => $user->FirstName,
                'LastName' => $user->LastName,
                'Role' => $user->Role,
            ]
        ]);
    }
}
