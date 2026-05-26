<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::orderBy('EmployeeId', 'asc')->get()->map(function ($u) {
            return [
                'EmployeeId' => $u->EmployeeId,
                'FirstName' => $u->FirstName,
                'MiddleName' => $u->MiddleName,
                'LastName' => $u->LastName,
                'Suffix' => $u->Suffix,
                'Role' => $u->Role,
                'PhoneNumber' => $u->PhoneNumber,
                'Birthday' => $u->Birthday,
                'Gender' => $u->Gender,
                'Address' => $u->Address,
                'Username' => $u->Username,
            ];
        });

        return response()->json($employees);
    }

    public function show($id)
    {
        $u = Employee::findOrFail($id);
        return response()->json([
            'EmployeeId' => $u->EmployeeId,
            'FirstName' => $u->FirstName,
            'MiddleName' => $u->MiddleName,
            'LastName' => $u->LastName,
            'Suffix' => $u->Suffix,
            'Role' => $u->Role,
            'PhoneNumber' => $u->PhoneNumber,
            'Birthday' => $u->Birthday,
            'Gender' => $u->Gender,
            'Address' => $u->Address,
            'Username' => $u->Username,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $employee = Employee::create($data);
        return response()->json($employee, 201);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $data = $request->all();

        if (array_key_exists('Password', $data)) {
            if (!$request->filled('OldPassword')) {
                return response()->json([
                    'errors' => [
                        'old_password' => ['Old password is required to change the password.']
                    ]
                ], 422);
            }

            if (!Hash::check($request->input('OldPassword'), $employee->Password)) {
                return response()->json([
                    'errors' => [
                        'old_password' => ['The old password is incorrect.']
                    ]
                ], 422);
            }
        }

        if (isset($data['Password']) && !$data['Password']) {
            unset($data['Password']);
        }

        unset($data['OldPassword']);

        $employee->update($data);
        return response()->json($employee);
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }
}
