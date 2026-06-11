<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MobileProfileController extends Controller
{
    public function show(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $user = User::where('EmployeeId', $employeeId)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Profile not found.',
            ], 404);
        }

        if (strtolower((string) $user->Role) !== 'flockman') {
            return response()->json([
                'message' => 'Only Flockman profiles are available on mobile.',
            ], 403);
        }

        return response()->json($this->transformUser($user));
    }

    public function update(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $user = User::where('EmployeeId', $employeeId)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Profile not found.',
            ], 404);
        }

        if (strtolower((string) $user->Role) !== 'flockman') {
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
            'username' => $user->Username,
            'first_name' => $user->FirstName,
            'middle_name' => $user->MiddleName,
            'last_name' => $user->LastName,
            'suffix' => $user->Suffix,
            'role' => $user->Role,
            'phone_number' => $user->PhoneNumber,
            'address' => $user->Address,
            'birthday' => $user->Birthday ? Carbon::parse($user->Birthday)->toDateString() : null,
            'gender' => $user->Gender,
        ];
    }
}