<?php

namespace App\Http\Controllers;

use App\Models\Pen;
use App\Models\WeightSamplingLog;
use App\Models\FeedRefillRecord;
use App\Models\VitaminRefillRecord;
use App\Models\CleaningLog;
use App\Models\SensorInspectionLog;
use App\Models\FlockBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class FarmActivityController extends Controller
{
    /**
     * Get Hatch and Mortality Check records from Pen model
     */
    public function pens()
    {
        $records = DB::table('pen as p')
            ->leftJoin('house as h', 'h.id', '=', 'p.house_id')
            ->whereNull('p.archived_at')
            ->where(function ($query) {
                $query->whereNull('h.id')
                    ->orWhereNull('h.archived_at');
            })
            ->select(
                'h.house_number',
                'p.pen_name',
                'p.eggs_hatched',
                'p.mortality',
                'p.recorded_at'
            )
            ->orderBy('h.id')
            ->orderBy('p.id')
            ->get();

        return response()->json(['records' => $records]);
    }

    /**
     * Get Weight Monitoring records from WeightSamplingLog model
     */
    public function weightSamplingLogs()
    {
        $records = DB::table('weight_sampling_logs')
            ->select(
                'house',
                'pen',
                'batch',
                'average_weight',
                'target',
                'status',
                'date'
            )
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json(['records' => $records]);
    }

    /**
     * Get Feed Replenishment records from FeedRefillRecord model
     */
    public function feedRefillRecords()
    {
        $records = DB::table('feed_refill_records as r')
            ->leftJoin('inventories as i', 'i.id', '=', 'r.inventory_id')
            ->leftJoin('house as h', 'h.id', '=', 'r.house_id')
            ->leftJoin('pen as p', 'p.id', '=', 'r.pen_id')
            ->select(
                'i.item_name as feed',
                'h.house_number',
                'p.pen_name',
                'r.feeder_number',
                'r.kilograms_used',
                'r.recorded_at'
            )
            ->orderByDesc('r.recorded_at')
            ->get();

        return response()->json(['records' => $records]);
    }

    /**
     * Get Vitamin Supplementation records from VitaminRefillRecord model
     */
    public function vitaminRefillRecords()
    {
        $records = DB::table('vitamin_refill_records as r')
            ->leftJoin('inventories as i', 'i.id', '=', 'r.inventory_id')
            ->leftJoin('house as h', 'h.id', '=', 'r.house_id')
            ->leftJoin('pen as p', 'p.id', '=', 'r.pen_id')
            ->select(
                'i.item_name as vitamin',
                'h.house_number',
                'p.pen_name',
                'r.bottles_used',
                'r.recorded_at'
            )
            ->orderByDesc('r.recorded_at')
            ->get();

        return response()->json(['records' => $records]);
    }

    /**
     * Get Cleaning Logs (Pen Disinfection and Pen Cleaning)
     */
    public function cleaningLogs()
    {
        $records = DB::table('cleaning_logs as c')
            ->whereIn('c.activity', ['Pen Disinfection', 'Pen Cleaning'])
            ->select(
                'c.house',
                'c.pen',
                'c.activity',
                'c.disinfectant_used',
                'c.performed_by',
                'c.date',
                'c.time'
            )
            ->orderByDesc('c.date')
            ->orderByDesc('c.id')
            ->get();

        return response()->json(['records' => $records]);
    }

    /**
     * Get Sensor Inspection logs
     */
    public function sensorInspectionLogs()
    {
        try {
            $records = DB::table('sensor_inspection_logs')
                ->select(
                    'house_id',
                    'pen_id',
                    'employee_id',
                    'sensor_present',
                    'sensor_clean_unblocked',
                    'no_visible_damage_or_loose_wiring',
                    'power_status_on',
                    'placement_secure',
                    'recorded_at'
                )
                ->orderByDesc('recorded_at')
                ->get();

            // Map with joins manually
            return response()->json(['records' => $records->map(function ($record) {
                $house = DB::table('house')->find($record->house_id);
                $pen = DB::table('pen')->find($record->pen_id);
                $employee = DB::table('user')->where('EmployeeId', $record->employee_id)->first();

                // Format employee name from FirstName, MiddleName, LastName, Suffix
                $employeeName = '--';
                if ($employee) {
                    $name_parts = [];
                    if ($employee->FirstName) $name_parts[] = $employee->FirstName;
                    if ($employee->MiddleName) $name_parts[] = $employee->MiddleName;
                    if ($employee->LastName) $name_parts[] = $employee->LastName;
                    if ($employee->Suffix) $name_parts[] = $employee->Suffix;
                    $employeeName = implode(' ', $name_parts);
                }

                // Format recorded_at timestamp
                $timestamp = $record->recorded_at ? \Carbon\Carbon::parse($record->recorded_at) : null;

                return [
                    'house_number' => $house->house_number ?? '--',
                    'pen_name' => $pen->pen_name ?? '--',
                    'sensor_present' => $record->sensor_present,
                    'sensor_clean_unblocked' => $record->sensor_clean_unblocked,
                    'no_visible_damage_or_loose_wiring' => $record->no_visible_damage_or_loose_wiring,
                    'power_status_on' => $record->power_status_on,
                    'placement_secure' => $record->placement_secure,
                    'date' => $timestamp ? $timestamp->format('Y-m-d') : '--',
                    'time' => $timestamp ? $timestamp->format('g:i A') : '--',
                    'performed_by' => $employeeName,
                ];
            })]);
        } catch (\Exception $e) {
            return response()->json(['records' => [], 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Flock Batches (Chick Placement)
     */
    public function flockBatches()
    {
        $records = DB::table('flock_batches as b')
            ->leftJoin('house as h', 'h.id', '=', 'b.house_id')
            ->leftJoin('pen as p', 'p.id', '=', 'b.pen_id')
            ->select(
                'b.batch_code',
                'h.house_number',
                'p.pen_name',
                'b.initial_population',
                'b.started_at',
                'b.status'
            )
            ->orderByDesc('b.started_at')
            ->get();

        return response()->json(['records' => $records]);
    }
}

