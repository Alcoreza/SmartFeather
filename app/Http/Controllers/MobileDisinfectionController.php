<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileDisinfectionController extends Controller
{
    public function getHouses()
    {
        $houses = House::orderBy('id', 'asc')
            ->get(['id', 'house_number', 'number_of_pens'])
            ->map(function (House $house) {
                return [
                    'id' => $house->id,
                    'house_number' => $house->house_number,
                    'number_of_pens' => $house->number_of_pens,
                ];
            })
            ->values();

        return response()->json($houses);
    }

    public function getPensByHouse($houseId)
    {
        $pens = Pen::where('house_id', $houseId)
            ->orderBy('id', 'asc')
            ->get(['id', 'pen_name'])
            ->map(function (Pen $pen) {
                return [
                    'id' => $pen->id,
                    'pen_name' => $pen->pen_name,
                ];
            })
            ->values();

        return response()->json($pens);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'activity' => 'required|string|max:255',
            'disinfectant_used' => 'required|string|max:255',
        ]);

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

        $now = now();

        DB::table('cleaning_logs')->insert([
            'house' => $house?->house_number,
            'pen' => $pen->pen_name,
            'activity' => $validated['activity'],
            'disinfectant_used' => $validated['disinfectant_used'],
            'performed_by' => $performedBy ?: (string) $validated['employee_id'],
            'date' => $now->toDateString(),
            'time' => $now->format('H:i:s'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disinfection submitted successfully.',
        ]);
    }
}
