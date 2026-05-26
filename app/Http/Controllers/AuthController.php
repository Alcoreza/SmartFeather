<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = trim((string) $request->username);
        $user = User::where('Username', $username)->first();

        $storedPassword = $user?->Password;
        $normalizedHash = $storedPassword;

        if ($normalizedHash && str_starts_with($normalizedHash, '$2a$')) {
            $normalizedHash = '$2y$' . substr($normalizedHash, 4);
        }

        if (
            !$user ||
            !$normalizedHash ||
            !password_verify($request->password, $normalizedHash)
        ) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        session([
            'user_id' => $user->EmployeeId,
            'role' => $user->Role,
        ]);

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'EmployeeId' => $user->EmployeeId,
                'Role' => $user->Role
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
