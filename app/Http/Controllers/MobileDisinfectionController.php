<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileDisinfectionController extends Controller
{
    public function getContext(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
        ]);

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before accessing disinfection.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 403);
        }

        $latestBiosecurity = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->orderByDesc('id')
            ->first();

        if (!$latestBiosecurity) {
            return response()->json([
                'message' => 'Please submit the personnel biosecurity form first before accessing disinfection.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 403);
        }

        $house = House::find($latestBiosecurity->house_id);

        if (!$house) {
            return response()->json([
                'message' => 'Assigned house from personnel biosecurity was not found.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 422);
        }

        $pens = Pen::where('house_id', $house->id)
            ->orderBy('id', 'asc')
            ->get(['id', 'pen_name'])
            ->map(function (Pen $pen) {
                return [
                    'id' => $pen->id,
                    'pen_name' => $pen->pen_name,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'access_allowed' => true,
            'house_id' => $house->id,
            'house_number' => $house->house_number,
            'pen_options' => $pens,
        ]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'activity' => 'required|string|max:255',
            'disinfectant_used' => 'required|string|max:255',
            'recorded_date' => 'required|date',
            'recorded_time' => 'required|date_format:H:i:s',
        ]);

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before recording disinfection.',
            ], 403);
        }

        $latestBiosecurity = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->orderByDesc('id')
            ->first();

        if (!$latestBiosecurity) {
            return response()->json([
                'message' => 'Please submit the personnel biosecurity form first before recording disinfection.',
            ], 403);
        }

        if ((int) $latestBiosecurity->house_id !== (int) $validated['house_id']) {
            return response()->json([
                'message' => 'You can only record disinfection for the house selected in your personnel biosecurity form.',
            ], 403);
        }

        $house = House::find($validated['house_id']);
        $pen = Pen::where('id', $validated['pen_id'])
            ->where('house_id', $validated['house_id'])
            ->first();

        if (!$pen) {
            return response()->json([
                'message' => 'Selected pen does not belong to the selected house.',
            ], 422);
        }

        $employee = Employee::find($validated['employee_id']);

        $performedBy = null;
        if ($employee) {
            $performedBy = trim(sprintf(
                '%s %s %s %s',
                $employee->FirstName ?? '',
                $employee->MiddleName ?? '',
                $employee->LastName ?? '',
                $employee->Suffix ?? '',
            ));
        }

        $createdAt = $validated['recorded_date'] . ' ' . $validated['recorded_time'];

        DB::table('cleaning_logs')->insert([
            'house' => $house?->house_number,
            'pen' => $pen->pen_name,
            'activity' => $validated['activity'],
            'disinfectant_used' => $validated['disinfectant_used'],
            'performed_by' => $performedBy ?: (string) $validated['employee_id'],
            'date' => $validated['recorded_date'],
            'time' => $validated['recorded_time'],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disinfection submitted successfully.',
        ]);
    }
}