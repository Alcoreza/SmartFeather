<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class ProfileController extends Controller
{
    public function getCurrentUser(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json([
            'EmployeeId' => $user->EmployeeId,
            'FirstName' => $user->FirstName,
            'MiddleName' => $user->MiddleName,
            'LastName' => $user->LastName,
            'Suffix' => $user->Suffix,
            'Role' => $user->Role,
            'PhoneNumber' => $user->PhoneNumber,
            'Birthday' => $user->Birthday,
            'Gender' => $user->Gender,
            'Address' => $user->Address,
            'Username' => $user->Username,
        ]);
    }
}
