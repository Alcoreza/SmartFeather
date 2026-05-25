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

        return response()->json($this->transformUser($user));
    }

    public function update(Request $request, $employeeId)
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

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^(\+639\d{9}|09\d{9})$/'],
            'address' => ['nullable', 'string', 'max:200'],
        ]);

        $user->PhoneNumber = $validated['phone_number'];
        $user->Address = $validated['address'] ?? null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'profile' => $this->transformUser($user->fresh()),
        ]);
    }

    private function transformUser(User $user): array
    {
        return [
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
        ];
    }
}