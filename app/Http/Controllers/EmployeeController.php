<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        return response()->json(User::all()->map(function ($u) {
            return [
                'EmployeeId' => $u->EmployeeId,
                'FirstName' => $u->FirstName,
                'MiddleName' => $u->MiddleName,
                'LastName' => $u->LastName,
                'Suffix' => $u->Suffix,
                'Role' => $u->Role,
                'PhoneNumber' => $u->PhoneNumber,
                'Birthday' => $u->Birthday ? $u->Birthday->format('Y-m-d') : null,
                'Gender' => $u->Gender,
                'Address' => $u->Address
            ];
        }));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'FirstName' => 'required',
            'MiddleName' => 'nullable',
            'LastName' => 'required',
            'Suffix' => 'nullable',
            'Role' => 'required',
            'PhoneNumber' => 'nullable|unique:user,PhoneNumber',
            'Birthday' => 'nullable|date',
            'Gender' => 'nullable',
            'Address' => 'nullable',
            'Password' => 'required|min:6'
        ]);

        return response()->json(User::create($data));
    }

    public function show($id)
    {
        $u = User::findOrFail($id);
        return response()->json([
            'EmployeeId' => $u->EmployeeId,
            'FirstName' => $u->FirstName,
            'MiddleName' => $u->MiddleName,
            'LastName' => $u->LastName,
            'Suffix' => $u->Suffix,
            'Role' => $u->Role,
            'PhoneNumber' => $u->PhoneNumber,
            'Birthday' => $u->Birthday ? $u->Birthday->format('Y-m-d') : null,
            'Gender' => $u->Gender,
            'Address' => $u->Address
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'FirstName' => 'required',
            'MiddleName' => 'nullable',
            'LastName' => 'required',
            'Suffix' => 'nullable',
            'Role' => 'required',
            'PhoneNumber' => 'nullable|unique:user,PhoneNumber,' . $id . ',EmployeeId',
            'Birthday' => 'nullable|date',
            'Gender' => 'nullable',
            'Address' => 'nullable',
            'Password' => 'nullable|min:6'
        ]);

        // Only update password if provided
        if (empty($data['Password'])) {
            unset($data['Password']);
        }

        $user->update($data);

        return response()->json([
            'EmployeeId' => $user->EmployeeId,
            'FirstName' => $user->FirstName,
            'MiddleName' => $user->MiddleName,
            'LastName' => $user->LastName,
            'Suffix' => $user->Suffix,
            'Role' => $user->Role,
            'PhoneNumber' => $user->PhoneNumber,
            'Birthday' => $user->Birthday ? $user->Birthday->format('Y-m-d') : null,
            'Gender' => $user->Gender,
            'Address' => $user->Address
        ]);
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
