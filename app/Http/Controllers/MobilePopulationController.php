<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobilePopulationController extends Controller
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
                'message' => 'Please scan IN and complete personnel biosecurity before accessing population recording.',
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
                'message' => 'Please submit the personnel biosecurity form first before accessing population recording.',
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
                preg_match('/(\d+)/', (string) $pen->pen_name, $matches);

                return [
                    'id' => $pen->id,
                    'pen_name' => $pen->pen_name,
                    'pen_number' => $matches[1] ?? $pen->pen_name,
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
            'task_id' => 'nullable|integer|exists:tasks,taskid',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'nullable|integer|exists:pen,id',
            'pen_name' => 'nullable|string',
            'eggs_hatched' => 'required|integer|min:0',
            'mortality' => 'required|integer|min:0',
            'recorded_at' => 'required|date',
        ]);

        if (empty($validated['pen_id']) && empty($validated['pen_name'])) {
            return response()->json([
                'message' => 'A pen is required.'
            ], 422);
        }

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before recording population.'
            ], 403);
        }

        $task = null;

        if (!empty($validated['task_id'])) {
            $task = DB::table('tasks')
                ->where('taskid', $validated['task_id'])
                ->where('user_employeeid', $validated['employee_id'])
                ->where('status', 'Pending')
                ->first();

            if (!$task) {
                return response()->json([
                    'message' => 'Pending task not found for this worker.'
                ], 404);
            }

            if ((int) $task->house_houseid !== (int) $validated['house_id']) {
                return response()->json([
                    'message' => 'This task must be recorded under its assigned house.'
                ], 422);
            }

            if (!empty($task->pennumber) && (int) $task->pennumber !== (int) ($validated['pen_id'] ?? 0)) {
                return response()->json([
                    'message' => 'This task must be recorded under its assigned pen.'
                ], 422);
            }
        }

        $biosecurityQuery = DB::table('personnel_biosecurity_logs')
            ->where('employee_id', $validated['employee_id'])
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->where('house_id', $validated['house_id']);

        if (!empty($validated['task_id'])) {
            $biosecurityQuery->where('task_id', $validated['task_id']);
        }

        if (!empty($validated['pen_id'])) {
            $biosecurityQuery->where('pen_id', $validated['pen_id']);
        }

        $latestBiosecurity = $biosecurityQuery
            ->orderByDesc('id')
            ->first();

        if (!$latestBiosecurity) {
            return response()->json([
                'message' => 'Please complete personnel biosecurity for this assigned task before recording population.'
            ], 403);
        }

        if (!empty($validated['pen_id'])) {
            $pen = Pen::where('house_id', $validated['house_id'])
                ->where('id', $validated['pen_id'])
                ->first();
        } else {
            $pen = Pen::where('house_id', $validated['house_id'])
                ->where('pen_name', $validated['pen_name'])
                ->first();
        }

        if (!$pen) {
            return response()->json([
                'message' => 'Pen not found.'
            ], 404);
        }

        $currentPopulation = (int) ($pen->population ?? 0);
        $newPopulation = max($currentPopulation - $validated['mortality'], 0);

        DB::transaction(function () use ($pen, $validated, $newPopulation) {
            DB::table('population_record')->insert([
                'task_id' => $validated['task_id'] ?? null,
                'pen_id' => $pen->id,
                'eggs_hatched' => $validated['eggs_hatched'],
                'mortality' => $validated['mortality'],
                'running_population' => $newPopulation,
                'recorded_at' => $validated['recorded_at'],
            ]);

            $pen->eggs_hatched = $validated['eggs_hatched'];
            $pen->mortality = $validated['mortality'];
            $pen->population = $newPopulation;
            $pen->recorded_at = $validated['recorded_at'];
            $pen->save();
        });

        return response()->json([
            'success' => true,
            'message' => !empty($validated['task_id'])
                ? 'Hatch and mortality data submitted successfully.'
                : 'Population data submitted successfully.'
        ]);
    }
}