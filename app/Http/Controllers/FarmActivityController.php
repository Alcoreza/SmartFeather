<?php

namespace App\Http\Controllers;

use App\Models\Pen;
use App\Models\WeightSamplingLog;
use App\Models\FeedRefillRecord;
use App\Models\VitaminRefillRecord;
use App\Models\CleaningLog;
use App\Models\SensorInspectionLog;
use App\Models\FlockBatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class FarmActivityController extends Controller
{
    /**
     * Get Hatch and Mortality Check records from PopulationRecord model
     */
    public function pens()
    {
        return response()->json(Cache::remember('farm_activity:pens', now()->addSeconds(30), function () {
        $records = DB::table('population_record as pr')
            ->leftJoin('pen as p', 'p.id', '=', 'pr.pen_id')
            ->leftJoin('house as h', 'h.id', '=', 'p.house_id')
            ->leftJoin('tasks as t', 't.taskid', '=', 'pr.task_id')
            ->leftJoin('user as u', 'u.EmployeeId', '=', 't.user_employeeid')
            ->select(
                'h.house_number',
                'p.pen_name',
                'pr.eggs_hatched',
                'pr.mortality',
                'pr.recorded_at',
                'u.FirstName',
                'u.MiddleName',
                'u.LastName',
                'u.Suffix'
            )
            ->orderByDesc('pr.recorded_at')
            ->orderByDesc('pr.id')
            ->get();

        // Map records to format performed_by
        return ['records' => $records->map(function ($record) {
            $employeeName = '--';
            if ($record->FirstName) {
                $name_parts = [];
                if ($record->FirstName) $name_parts[] = $record->FirstName;
                if ($record->MiddleName) $name_parts[] = $record->MiddleName;
                if ($record->LastName) $name_parts[] = $record->LastName;
                if ($record->Suffix) $name_parts[] = $record->Suffix;
                $employeeName = implode(' ', $name_parts);
            }

            return [
                'house_number' => $record->house_number ?? '--',
                'pen_name' => $record->pen_name ?? '--',
                'eggs_hatched' => $record->eggs_hatched ?? 0,
                'mortality' => $record->mortality ?? 0,
                'recorded_at' => $record->recorded_at,
                'performed_by' => $employeeName,
            ];
        })];
        }));
    }

    /**
     * Get Weight Monitoring records from WeightSamplingLog model
     */
    public function weightSamplingLogs()
    {
        return response()->json(Cache::remember('farm_activity:weight_sampling', now()->addSeconds(30), function () {
        $records = DB::table('weight_sampling_logs as w')
            ->leftJoin('tasks as t', 't.taskid', '=', 'w.task_id')
            ->leftJoin('user as u', 'u.EmployeeId', '=', 't.user_employeeid')
            ->select(
                'w.house',
                'w.pen',
                'w.batch',
                'w.average_weight',
                'w.target',
                'w.status',
                'w.date',
                'u.FirstName',
                'u.MiddleName',
                'u.LastName',
                'u.Suffix'
            )
            ->orderByDesc('w.date')
            ->orderByDesc('w.id')
            ->get();

        // Map records to format performed_by
        return ['records' => $records->map(function ($record) {
            $employeeName = '--';
            if ($record->FirstName) {
                $name_parts = [];
                if ($record->FirstName) $name_parts[] = $record->FirstName;
                if ($record->MiddleName) $name_parts[] = $record->MiddleName;
                if ($record->LastName) $name_parts[] = $record->LastName;
                if ($record->Suffix) $name_parts[] = $record->Suffix;
                $employeeName = implode(' ', $name_parts);
            }

            return [
                'house' => $record->house,
                'pen' => $record->pen,
                'batch' => $record->batch,
                'average_weight' => $record->average_weight,
                'target' => $record->target,
                'status' => $record->status,
                'date' => $record->date,
                'performed_by' => $employeeName,
            ];
        })];
        }));
    }

    /**
     * Get Feed Replenishment records from FeedRefillRecord model
     */
    public function feedRefillRecords()
    {
        return response()->json(Cache::remember('farm_activity:feed_refill', now()->addSeconds(30), function () {
        $records = DB::table('feed_refill_records as r')
            ->leftJoin('inventories as i', 'i.id', '=', 'r.inventory_id')
            ->leftJoin('house as h', 'h.id', '=', 'r.house_id')
            ->leftJoin('pen as p', 'p.id', '=', 'r.pen_id')
            ->leftJoin('tasks as t', 't.taskid', '=', 'r.task_id')
            ->leftJoin('user as u', 'u.EmployeeId', '=', 't.user_employeeid')
            ->select(
                'i.item_name as feed',
                'h.house_number',
                'p.pen_name',
                'r.feeder_number',
                'r.kilograms_used',
                'r.recorded_at',
                'u.FirstName',
                'u.MiddleName',
                'u.LastName',
                'u.Suffix'
            )
            ->orderByDesc('r.recorded_at')
            ->get();

        // Map records to format performed_by
        return ['records' => $records->map(function ($record) {
            $employeeName = '--';
            if ($record->FirstName) {
                $name_parts = [];
                if ($record->FirstName) $name_parts[] = $record->FirstName;
                if ($record->MiddleName) $name_parts[] = $record->MiddleName;
                if ($record->LastName) $name_parts[] = $record->LastName;
                if ($record->Suffix) $name_parts[] = $record->Suffix;
                $employeeName = implode(' ', $name_parts);
            }

            return [
                'feed' => $record->feed,
                'house_number' => $record->house_number,
                'pen_name' => $record->pen_name,
                'feeder_number' => $record->feeder_number,
                'kilograms_used' => $record->kilograms_used,
                'recorded_at' => $record->recorded_at,
                'performed_by' => $employeeName,
            ];
        })];
        }));
    }

    /**
     * Get Vitamin Supplementation records from VitaminRefillRecord model
     */
    public function vitaminRefillRecords()
    {
        return response()->json(Cache::remember('farm_activity:vitamin_refill', now()->addSeconds(30), function () {
        $records = DB::table('vitamin_refill_records as r')
            ->leftJoin('inventories as i', 'i.id', '=', 'r.inventory_id')
            ->leftJoin('house as h', 'h.id', '=', 'r.house_id')
            ->leftJoin('pen as p', 'p.id', '=', 'r.pen_id')
            ->leftJoin('tasks as t', 't.taskid', '=', 'r.task_id')
            ->leftJoin('user as u', 'u.EmployeeId', '=', 't.user_employeeid')
            ->select(
                'i.item_name as vitamin',
                'h.house_number',
                'p.pen_name',
                'r.bottles_used',
                'r.recorded_at',
                'u.FirstName',
                'u.MiddleName',
                'u.LastName',
                'u.Suffix'
            )
            ->orderByDesc('r.recorded_at')
            ->get();

        // Map records to format performed_by
        return ['records' => $records->map(function ($record) {
            $employeeName = '--';
            if ($record->FirstName) {
                $name_parts = [];
                if ($record->FirstName) $name_parts[] = $record->FirstName;
                if ($record->MiddleName) $name_parts[] = $record->MiddleName;
                if ($record->LastName) $name_parts[] = $record->LastName;
                if ($record->Suffix) $name_parts[] = $record->Suffix;
                $employeeName = implode(' ', $name_parts);
            }

            return [
                'vitamin' => $record->vitamin,
                'house_number' => $record->house_number,
                'pen_name' => $record->pen_name,
                'bottles_used' => $record->bottles_used,
                'recorded_at' => $record->recorded_at,
                'performed_by' => $employeeName,
            ];
        })];
        }));
    }

    /**
     * Get Cleaning Logs (Pen Disinfection and Pen Cleaning)
     */
    public function cleaningLogs()
    {
        $records = Cache::remember('farm_activity:cleaning', now()->addSeconds(30), function () {
            return DB::table('cleaning_logs as c')
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
        });

        return response()->json(['records' => $records]);
    }

    /**
     * Get Sensor Inspection logs - OPTIMIZED with proper joins
     */
    public function sensorInspectionLogs()
    {
        try {
            return response()->json(Cache::remember('farm_activity:sensor_inspection', now()->addSeconds(30), function () {
            $records = DB::table('sensor_inspection_logs as sil')
                ->leftJoin('house as h', 'h.id', '=', 'sil.house_id')
                ->leftJoin('pen as p', 'p.id', '=', 'sil.pen_id')
                ->leftJoin('user as u', 'u.EmployeeId', '=', 'sil.employee_id')
                ->select(
                    'h.house_number',
                    'p.pen_name',
                    'u.FirstName',
                    'u.MiddleName',
                    'u.LastName',
                    'u.Suffix',
                    'sil.sensor_present',
                    'sil.sensor_clean_unblocked',
                    'sil.no_visible_damage_or_loose_wiring',
                    'sil.power_status_on',
                    'sil.placement_secure',
                    'sil.recorded_at'
                )
                ->orderByDesc('sil.recorded_at')
                ->get();

            return ['records' => $records->map(function ($record) {
                // Format employee name from FirstName, MiddleName, LastName, Suffix
                $employeeName = '--';
                if ($record->FirstName || $record->LastName) {
                    $name_parts = [];
                    if ($record->FirstName) $name_parts[] = $record->FirstName;
                    if ($record->MiddleName) $name_parts[] = $record->MiddleName;
                    if ($record->LastName) $name_parts[] = $record->LastName;
                    if ($record->Suffix) $name_parts[] = $record->Suffix;
                    $employeeName = implode(' ', $name_parts);
                }

                // Parse and format timestamp
                $timestamp = $record->recorded_at ? \Carbon\Carbon::parse($record->recorded_at) : null;

                return [
                    'house_number' => $record->house_number ?? '--',
                    'pen_name' => $record->pen_name ?? '--',
                    'sensor_present' => (bool) $record->sensor_present,
                    'sensor_clean_unblocked' => (bool) $record->sensor_clean_unblocked,
                    'no_visible_damage_or_loose_wiring' => (bool) $record->no_visible_damage_or_loose_wiring,
                    'power_status_on' => (bool) $record->power_status_on,
                    'placement_secure' => (bool) $record->placement_secure,
                    'date' => $timestamp ? $timestamp->format('Y-m-d') : '--',
                    'time' => $timestamp ? $timestamp->format('g:i A') : '--',
                    'performed_by' => $employeeName,
                ];
            })];
            }));
        } catch (\Exception $e) {
            return response()->json(['records' => [], 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Flock Batches (Chick Placement)
     */
    public function flockBatches()
    {
        return response()->json(Cache::remember('farm_activity:flock_batches', now()->addSeconds(30), function () {
        $records = DB::table('flock_batches as b')
            ->leftJoin('house as h', 'h.id', '=', 'b.house_id')
            ->leftJoin('pen as p', 'p.id', '=', 'b.pen_id')
            ->leftJoin('tasks as t', 't.taskid', '=', 'b.task_id')
            ->leftJoin('user as u', 'u.EmployeeId', '=', 't.user_employeeid')
            ->select(
                'b.batch_code',
                'h.house_number',
                'p.pen_name',
                'b.initial_population',
                'b.started_at',
                'b.status',
                'u.FirstName',
                'u.MiddleName',
                'u.LastName',
                'u.Suffix'
            )
            ->orderByDesc('b.started_at')
            ->get();

        // Map records to format performed_by
        return ['records' => $records->map(function ($record) {
            $employeeName = '--';
            if ($record->FirstName) {
                $name_parts = [];
                if ($record->FirstName) $name_parts[] = $record->FirstName;
                if ($record->MiddleName) $name_parts[] = $record->MiddleName;
                if ($record->LastName) $name_parts[] = $record->LastName;
                if ($record->Suffix) $name_parts[] = $record->Suffix;
                $employeeName = implode(' ', $name_parts);
            }

            return [
                'batch_code' => $record->batch_code,
                'house_number' => $record->house_number,
                'pen_name' => $record->pen_name,
                'initial_population' => $record->initial_population,
                'started_at' => $record->started_at,
                'status' => $record->status,
                'performed_by' => $employeeName,
            ];
        })];
        }));
    }
}
