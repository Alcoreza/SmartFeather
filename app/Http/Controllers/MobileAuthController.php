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

        if (!$user || !$normalizedHash || !$passwordMatches) {
            return response()->json([
                'message' => 'Invalid username or password.',
            ], 401);
        }

        if (strtolower((string) $user->Role) !== 'flockman') {
            return response()->json([
                'message' => 'Only Flockman accounts can sign in on mobile.',
            ], 403);
        }

        $plainToken = Str::random(80);

        DB::table('mobile_api_tokens')->insert([
            'employee_id' => $user->EmployeeId,
            'token_hash' => hash('sha256', $plainToken),
            'name' => 'android',
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