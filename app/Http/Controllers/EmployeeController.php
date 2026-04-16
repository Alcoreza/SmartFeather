<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::all()->map(function ($u) {
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
            ];
        });

        return response()->json($employees);
    }

    public function show($id)
    {
        $u = Employee::findOrFail($id);
        return response()->json($u);
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

        if (isset($data['Password']) && !$data['Password']) {
            unset($data['Password']);
        }

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
