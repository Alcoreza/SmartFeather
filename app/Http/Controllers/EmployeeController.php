<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|unique:users,id',
            'first_name' => 'required',
            'middle_name' => 'nullable',
            'last_name' => 'required',
            'suffix' => 'nullable',
            'role' => 'required',
            'phone_number' => 'nullable',
            'birthday' => 'nullable',
            'gender' => 'nullable',
            'address' => 'nullable'
        ]);

        return response()->json(User::create($data));
    }

    public function show($id)
    {
        return response()->json(User::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'first_name' => 'required',
            'middle_name' => 'nullable',
            'last_name' => 'required',
            'suffix' => 'nullable',
            'role' => 'required',
            'phone_number' => 'nullable',
            'birthday' => 'nullable',
            'gender' => 'nullable',
            'address' => 'nullable'
        ]);

        $user->update($data);

        return response()->json($user);
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted']);
    }
}