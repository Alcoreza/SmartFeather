<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    protected function currentUser()
    {
        $userId = session('user_id');

        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }

    public function managerProfile()
    {
        $user = $this->currentUser();

        if (!$user) {
            return redirect()->route('login');
        }

        return view('manager.profile', [
            'user' => $user,
            'birthdayDisplay' => $user->Birthday
                ? \Illuminate\Support\Carbon::parse($user->Birthday)->format('F j, Y')
                : '',
        ]);
    }

    public function adminProfile()
    {
        $user = $this->currentUser();

        if (!$user) {
            return redirect()->route('login');
        }

        return view('admin.profile', [
            'user' => $user,
            'birthdayDisplay' => $user->Birthday
                ? \Illuminate\Support\Carbon::parse($user->Birthday)->format('F j, Y')
                : '',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->currentUser();

        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'PhoneNumber' => [
                'required',
                'string',
                'size:11',
                'regex:/^09\d{9}$/',
                Rule::unique('user', 'PhoneNumber')->ignore($user->EmployeeId, 'EmployeeId'),
            ],
            'Address' => ['required', 'string', 'max:255'],
        ], [
            'PhoneNumber.regex' => 'Invalid phone number.',
            'PhoneNumber.size' => 'Invalid phone number.',
            'PhoneNumber.unique' => 'Phone number already in use.',
        ]);

        $user->update($validated);

        return redirect()->back()->with('profile_success', 'Profile updated successfully.');
    }

    public function checkPhone(Request $request)
    {
        $user = $this->currentUser();

        if (!$user) {
            return response()->json(['message' => 'Not authenticated'], 401);
        }

        $validated = $request->validate([
            'PhoneNumber' => ['required', 'string', 'size:11', 'regex:/^09\d{9}$/'],
        ], [
            'PhoneNumber.regex' => 'Invalid phone number.',
            'PhoneNumber.size' => 'Invalid phone number.',
        ]);

        $exists = User::where('PhoneNumber', $validated['PhoneNumber'])
            ->where('EmployeeId', '!=', $user->EmployeeId)
            ->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Phone number already in use.' : '',
        ], $exists ? 409 : 200);
    }

    public function getCurrentUser(Request $request)
    {
        $user = $this->currentUser();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
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
