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
            !$user->is_active ||
            !$normalizedHash ||
            !password_verify($request->password, $normalizedHash)
        ) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Store user data in session
        $request->session()->regenerate(); // Prevent session fixation attacks
        
        session([
            'user_id' => $user->EmployeeId,
            'user_name' => $user->Username,
            'role' => $user->Role,
            'email' => $user->Email ?? null,
            'logged_in_at' => now(),
        ]);

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'EmployeeId' => $user->EmployeeId,
                'Username' => $user->Username,
                'Role' => $user->Role
            ]
        ]);
    }

    public function logout(Request $request)
    {
        // Completely destroy the session
        $request->session()->flush();
        $request->session()->regenerateToken();

        // Redirect to login with cache control headers
        return redirect('/')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
            ->with('message', 'Logged out successfully');
    }
}
