<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileVisitorController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'date' => 'required|date',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'required|date_format:H:i',
            'name' => 'required|string|max:100',
            'purpose' => 'required|string|max:255',
            'foot_bath' => 'required|boolean',
            'sanitation' => 'required|boolean',
            'ppe' => 'required|boolean',
        ]);

        $employee = Employee::find($validated['employee_id']);

        $monitoredBy = null;
        if ($employee) {
            $monitoredBy = trim(sprintf(
                '%s %s %s %s',
                $employee->FirstName ?? '',
                $employee->MiddleName ?? '',
                $employee->LastName ?? '',
                $employee->Suffix ?? '',
            ));
        }

        DB::table('visitor_logs')->insert([
            'date' => $validated['date'],
            'time_in' => $validated['time_in'],
            'time_out' => $validated['time_out'],
            'name' => $validated['name'],
            'purpose' => $validated['purpose'],
            'foot_bath' => $validated['foot_bath'] ? 'Yes' : 'No',
            'sanitation' => $validated['sanitation'] ? 'Yes' : 'No',
            'ppe' => $validated['ppe'] ? 'Yes' : 'No',
            'monitored_by' => $monitoredBy ?: (string) $validated['employee_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visitor log submitted successfully.',
        ]);
    }
}
