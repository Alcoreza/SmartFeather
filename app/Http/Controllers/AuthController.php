<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('EmployeeId', $request->user_id)->first();

        if (!$user || !Hash::check($request->password, $user->Password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Optional: store session
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
