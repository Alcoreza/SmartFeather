<?php

namespace App\Http\Controllers;

use App\Models\Pen;
use App\Models\Employee;
use App\Models\WeightSamplingLog;
use App\Models\FeedRefillRecord;
use App\Models\VitaminRefillRecord;
use App\Models\CleaningLog;
use App\Models\SensorInspectionLog;
use App\Models\FlockBatch;
use App\Models\House;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class FarmActivityController extends Controller
{
    public function filterOptions()
    {
        return response()->json(Cache::remember('farm_activity_filter_options', now()->addSeconds(30), function () {
            $houses = House::query()
                ->whereNull('archived_at')
                ->orderBy('house_number', 'asc')
                ->get()
                ->map(function (House $house) {
                    return [
                        'id' => $house->id,
                        'number' => $house->house_number,
                    ];
                });

            $flockmen = Employee::where('Role', 'Flockman')
                ->whereRaw('is_active is true')
                ->orderBy('EmployeeId', 'asc')
                ->get()
                ->map(function (Employee $employee) {
                    return [
                        'id' => $employee->EmployeeId,
                        'name' => $this->employeeName($employee),
                    ];
                });

            return [
                'houses' => $houses,
                'flockmen' => $flockmen,
            ];
        }));
    }

    /**
     * Get Hatch and Mortality Check records from PopulationRecord model
     */
    public function pens(Request $request)
    {
        $filters = $this->getDateFilters($request);

        return response()->json(Cache::remember($this->cacheKey('pens', $filters), now()->addSeconds(30), function () use ($filters) {
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
            ->orderByDesc('pr.id');

        $this->applyDateRange($records, 'pr.recorded_at', $filters);
        $this->applyHouseFilter($records, 'h.id', $filters);
        $this->applyFlockmanFilter($records, 't.user_employeeid', $filters);
        $records = $records->get();

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
    public function weightSamplingLogs(Request $request)
    {
        $filters = $this->getDateFilters($request);

        return response()->json(Cache::remember($this->cacheKey('weight_sampling', $filters), now()->addSeconds(30), function () use ($filters) {
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
            ->orderByDesc('w.id');

        $this->applyDateRange($records, 'w.date', $filters);
        $this->applyHouseFilter($records, 'w.house_id', $filters);
        $this->applyFlockmanFilter($records, 't.user_employeeid', $filters);
        $records = $records->get();

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
    public function feedRefillRecords(Request $request)
    {
        $filters = $this->getDateFilters($request);

        return response()->json(Cache::remember($this->cacheKey('feed_refill', $filters), now()->addSeconds(30), function () use ($filters) {
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
            ->orderByDesc('r.recorded_at');

        $this->applyDateRange($records, 'r.recorded_at', $filters);
        $this->applyHouseFilter($records, 'r.house_id', $filters);
        $this->applyFlockmanFilter($records, 't.user_employeeid', $filters);
        $records = $records->get();

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
    public function vitaminRefillRecords(Request $request)
    {
        $filters = $this->getDateFilters($request);

        return response()->json(Cache::remember($this->cacheKey('vitamin_refill', $filters), now()->addSeconds(30), function () use ($filters) {
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
            ->orderByDesc('r.recorded_at');

        $this->applyDateRange($records, 'r.recorded_at', $filters);
        $this->applyHouseFilter($records, 'r.house_id', $filters);
        $this->applyFlockmanFilter($records, 't.user_employeeid', $filters);
        $records = $records->get();

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
    public function cleaningLogs(Request $request)
    {
        $filters = $this->getDateFilters($request);

        $records = Cache::remember($this->cacheKey('cleaning', $filters), now()->addSeconds(30), function () use ($filters) {
            $query = DB::table('cleaning_logs as c')
            ->leftJoin('tasks as t', 't.taskid', '=', 'c.task_id')
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
            ->orderByDesc('c.id');

            $this->applyDateRange($query, 'c.date', $filters);
            $this->applyHouseFilter($query, 'c.house_id', $filters);
            $this->applyCleaningFlockmanFilter($query, $filters);

            return $query->get();
        });

        return response()->json(['records' => $records]);
    }

    /**
     * Get Sensor Inspection logs - OPTIMIZED with proper joins
     */
    public function sensorInspectionLogs(Request $request)
    {
        $filters = $this->getDateFilters($request);

        try {
            return response()->json(Cache::remember($this->cacheKey('sensor_inspection', $filters), now()->addSeconds(30), function () use ($filters) {
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
                ->orderByDesc('sil.recorded_at');

            $this->applyDateRange($records, 'sil.recorded_at', $filters);
            $this->applyHouseFilter($records, 'sil.house_id', $filters);
            $this->applyFlockmanFilter($records, 'sil.employee_id', $filters);
            $records = $records->get();

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
    public function flockBatches(Request $request)
    {
        $filters = $this->getDateFilters($request);

        return response()->json(Cache::remember($this->cacheKey('flock_batches', $filters), now()->addSeconds(30), function () use ($filters) {
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
            ->orderByDesc('b.started_at');

        $this->applyDateRange($records, 'b.started_at', $filters);
        $this->applyHouseFilter($records, 'b.house_id', $filters);
        $this->applyFlockmanFilter($records, 't.user_employeeid', $filters);
        $records = $records->get();

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

    private function getDateFilters(Request $request): array
    {
        return [
            'from_date' => $request->query('from_date', ''),
            'to_date' => $request->query('to_date', ''),
            'house_id' => $request->query('house_id', ''),
            'flockman_id' => $request->query('flockman_id', ''),
        ];
    }

    private function cacheKey(string $recordType, array $filters): string
    {
        return 'farm_activity:' . $recordType . ':' . md5(json_encode($filters));
    }

    private function applyDateRange($query, string $column, array $filters): void
    {
        if (!empty($filters['from_date'])) {
            $query->whereDate($column, '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate($column, '<=', $filters['to_date']);
        }
    }

    private function applyHouseFilter($query, string $column, array $filters): void
    {
        if (!empty($filters['house_id'])) {
            $query->where($column, (int) $filters['house_id']);
        }
    }

    private function applyFlockmanFilter($query, string $column, array $filters): void
    {
        if (!empty($filters['flockman_id'])) {
            $query->where($column, (int) $filters['flockman_id']);
        }
    }

    private function applyCleaningFlockmanFilter($query, array $filters): void
    {
        if (empty($filters['flockman_id'])) {
            return;
        }

        $flockmanId = (int) $filters['flockman_id'];
        $flockman = Employee::find($flockmanId);
        $flockmanName = $flockman ? $this->employeeName($flockman) : '';

        $query->where(function ($query) use ($flockmanId, $flockmanName) {
            $query->where('t.user_employeeid', $flockmanId);

            if ($flockmanName !== '') {
                $query->orWhere('c.performed_by', $flockmanName);
            }
        });
    }

    private function employeeName(Employee $employee): string
    {
        $fullName = trim(sprintf(
            '%s %s %s %s',
            $employee->FirstName ?? '',
            $employee->MiddleName ?? '',
            $employee->LastName ?? '',
            $employee->Suffix ?? '',
        ));

        return $fullName ?: 'Unknown';
    }
}
