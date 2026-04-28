<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class MobileProfileController extends Controller
{
    public function show($employeeId)
    {
        $user = User::where('EmployeeId', $employeeId)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Profile not found.',
            ], 404);
        }

        if (strtolower($user->Role) !== 'flockman') {
            return response()->json([
                'message' => 'Only Flockman profiles are available on mobile.',
            ], 403);
        }

        return response()->json([
            'employee_id' => (int) $user->EmployeeId,
            'first_name' => $user->FirstName,
            'middle_name' => $user->MiddleName,
            'last_name' => $user->LastName,
            'suffix' => $user->Suffix,
            'role' => $user->Role,
            'phone_number' => $user->PhoneNumber,
            'address' => $user->Address,
            'birthday' => $user->Birthday ? \Illuminate\Support\Carbon::parse($user->Birthday)->toDateString() : null,
            'gender' => $user->Gender,
        ]);
    }
}
