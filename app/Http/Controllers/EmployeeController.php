<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    private const PHONE_RULE = 'regex:/^09\d{9}$/';

    public function index()
    {
        $employees = Employee::whereRaw('is_active is true')
            ->orderBy('EmployeeId', 'asc')
            ->get()
            ->map(function ($u) {
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
                'is_active' => (bool) $u->is_active,
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
            'is_active' => (bool) $u->is_active,
        ]);
    }

    public function store(Request $request)
    {
        $this->trimEmployeeRequest($request);
        $data = $request->validate($this->employeeRules(null, true), $this->employeeMessages());
        $data = $this->trimEmployeeData($data);
        $this->ensureUniqueEmployeeName($data);

        $employee = Employee::create($data);
        return response()->json($employee, 201);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $this->trimEmployeeRequest($request);
        $data = $request->validate($this->employeeRules($employee->EmployeeId, false), $this->employeeMessages());
        $data = $this->trimEmployeeData($data);
        $this->ensureUniqueEmployeeName($data, $employee->EmployeeId);

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

    public function checkPhone(Request $request)
    {
        $validated = $request->validate([
            'PhoneNumber' => ['required', 'string', 'size:11', self::PHONE_RULE],
            'EmployeeId' => ['nullable', 'integer'],
        ], $this->employeeMessages());

        $exists = Employee::query()
            ->where('PhoneNumber', $validated['PhoneNumber'])
            ->when($validated['EmployeeId'] ?? null, function ($query, $employeeId) {
                $query->where('EmployeeId', '!=', $employeeId);
            })
            ->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists
                ? 'This phone number is already assigned to another employee.'
                : 'Phone number is available.',
        ]);
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        DB::table('user')
            ->where('EmployeeId', $employee->EmployeeId)
            ->update(['is_active' => DB::raw('false')]);

        return response()->json(['message' => 'Employee deactivated successfully.'], 200);
    }

    private function employeeRules(?int $employeeId, bool $isCreate): array
    {
        $required = $isCreate ? ['required'] : ['sometimes', 'required'];

        return [
            'FirstName' => [...$required, 'string', 'max:100'],
            'MiddleName' => ['nullable', 'string', 'max:100'],
            'LastName' => [...$required, 'string', 'max:100'],
            'Suffix' => ['nullable', 'string', 'max:20'],
            'Role' => [...$required, 'string', Rule::in(['Manager', 'Admin', 'Flockman'])],
            'PhoneNumber' => [
                ...$required,
                'string',
                'size:11',
                self::PHONE_RULE,
                Rule::unique('user', 'PhoneNumber')->ignore($employeeId, 'EmployeeId'),
            ],
            'Birthday' => [...$required, 'date', 'before:today'],
            'Gender' => [...$required, 'string', Rule::in(['Male', 'Female'])],
            'Address' => ['nullable', 'string', 'max:255'],
            'Username' => [
                ...$required,
                'string',
                'max:100',
                Rule::unique('user', 'Username')->ignore($employeeId, 'EmployeeId'),
            ],
            'Password' => [$isCreate ? 'required' : 'sometimes', 'string', 'min:8', 'max:255'],
            'OldPassword' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function employeeMessages(): array
    {
        return [
            'PhoneNumber.regex' => 'Phone number must use 09XXXXXXXXX format.',
            'PhoneNumber.size' => 'Phone number must be exactly 11 digits.',
            'PhoneNumber.unique' => 'This phone number is already assigned to another employee.',
            'Username.unique' => 'This username is already assigned to another employee.',
            'Birthday.before' => 'Birthday must be earlier than today.',
            'Role.in' => 'Select a valid role.',
            'Gender.in' => 'Select a valid gender.',
            'Password.min' => 'Password must be at least 8 characters.',
        ];
    }

    private function ensureUniqueEmployeeName(array $data, ?int $employeeId = null): void
    {
        $firstName = strtolower($data['FirstName'] ?? '');
        $lastName = strtolower($data['LastName'] ?? '');
        $suffix = strtolower($data['Suffix'] ?? '');

        if ($firstName === '' || $lastName === '') {
            return;
        }

        $duplicateExists = Employee::query()
            ->whereRaw('lower("FirstName") = ?', [$firstName])
            ->whereRaw('lower("LastName") = ?', [$lastName])
            ->whereRaw('lower(coalesce("Suffix", \'\')) = ?', [$suffix])
            ->when($employeeId, function ($query) use ($employeeId) {
                $query->where('EmployeeId', '!=', $employeeId);
            })
            ->exists();

        if (! $duplicateExists) {
            return;
        }

        throw ValidationException::withMessages([
            'FirstName' => ['An employee with the same first name, last name, and suffix already exists.'],
            'LastName' => ['An employee with the same first name, last name, and suffix already exists.'],
            'Suffix' => ['Use a suffix such as Jr. or III if this is a different employee.'],
        ]);
    }

    private function trimEmployeeData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        return $data;
    }

    private function trimEmployeeRequest(Request $request): void
    {
        $request->merge($this->trimEmployeeData($request->all()));
    }
}
