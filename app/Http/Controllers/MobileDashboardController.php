<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileDashboardController extends Controller
{
    public function show(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'environment_house_id' => 'nullable|integer',
            'environment_pen_id' => 'nullable|integer',
            'resource_house_id' => 'nullable|integer',
            'resource_pen_id' => 'nullable|integer',
        ]);

        $latestPopulationDate = DB::table('population_record')
            ->selectRaw('DATE(recorded_at) as record_date')
            ->orderByRaw('DATE(recorded_at) desc')
            ->value('record_date');

        $dailyEggs = 0;
        $dailyMortalities = 0;
        $overviewDateLabel = Carbon::now()->format('n/j/y');

        if (!empty($latestPopulationDate)) {
            $dailyTotals = DB::table('population_record')
                ->selectRaw('COALESCE(SUM(eggs_hatched), 0) as total_eggs')
                ->selectRaw('COALESCE(SUM(mortality), 0) as total_mortalities')
                ->whereDate('recorded_at', $latestPopulationDate)
                ->first();

            $dailyEggs = (int) ($dailyTotals->total_eggs ?? 0);
            $dailyMortalities = (int) ($dailyTotals->total_mortalities ?? 0);
            $overviewDateLabel = Carbon::parse($latestPopulationDate)->format('n/j/y');
        }

        $totalBirds = (int) DB::table('pen')
            ->whereNotNull('current_batch_id')
            ->sum('population');

        $pendingTasksQuery = DB::table('tasks as t')
            ->leftJoin('house as h', 't.house_houseid', '=', 'h.id')
            ->leftJoin('pen as p', function ($join) {
                $join->on('t.pennumber', '=', 'p.id')
                    ->on('t.house_houseid', '=', 'p.house_id');
            })
            ->where('t.user_employeeid', $validated['employee_id'])
            ->where('t.status', 'Pending');

        $pendingTasks = (int) $pendingTasksQuery->count();

        $pendingTask = DB::table('tasks as t')
            ->leftJoin('house as h', 't.house_houseid', '=', 'h.id')
            ->leftJoin('pen as p', function ($join) {
                $join->on('t.pennumber', '=', 'p.id')
                    ->on('t.house_houseid', '=', 'p.house_id');
            })
            ->where('t.user_employeeid', $validated['employee_id'])
            ->where('t.status', 'Pending')
            ->orderByRaw("
                CASE
                    WHEN t.prioritylevel = 'High' THEN 1
                    WHEN t.prioritylevel = 'Medium' THEN 2
                    WHEN t.prioritylevel = 'Low' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('t.finishby')
            ->orderByDesc('t.timeassigned')
            ->select(
                't.tasktype',
                't.detailedtask',
                't.finishby',
                't.prioritylevel',
                'h.house_number',
                'p.pen_name'
            )
            ->first();

        $environmentFilterOptions = $this->buildSensorFilterOptions(['temperature', 'ammonia']);
        $resourceFilterOptions = $this->buildSensorFilterOptions(['feed', 'water']);

        $environmentSelection = $this->resolveSelection(
            $environmentFilterOptions,
            $validated['environment_house_id'] ?? null,
            $validated['environment_pen_id'] ?? null
        );

        $resourceSelection = $this->resolveSelection(
            $resourceFilterOptions,
            $validated['resource_house_id'] ?? null,
            $validated['resource_pen_id'] ?? null
        );

        $environmentReadings = $this->latestReadingsByType(
            ['temperature', 'ammonia'],
            $environmentSelection['house_id'],
            $environmentSelection['pen_id']
        );

        $resourceReadings = $this->latestReadingsByType(
            ['feed', 'water'],
            $resourceSelection['house_id'],
            $resourceSelection['pen_id']
        );

        return response()->json([
            'success' => true,
            'welcome_text' => 'Welcome!',
            'overview_date_label' => "for {$overviewDateLabel}",
            'stats' => [
                [
                    'title' => 'Total Birds',
                    'value' => (string) $totalBirds,
                    'icon_key' => 'birds',
                    'bg_color' => '#FDFDFD',
                    'icon_bg' => '#D92C2C',
                ],
                [
                    'title' => 'Total Eggs',
                    'value' => (string) $dailyEggs,
                    'icon_key' => 'eggs',
                    'bg_color' => '#FDFDFD',
                    'icon_bg' => '#FFA96E',
                ],
                [
                    'title' => 'Mortalities',
                    'value' => (string) $dailyMortalities,
                    'icon_key' => 'mortalities',
                    'bg_color' => '#FDFDFD',
                    'icon_bg' => '#808080',
                ],
            ],
            'environment_filter' => [
                'selected_house_id' => $environmentSelection['house_id'],
                'selected_pen_id' => $environmentSelection['pen_id'],
                'options' => $environmentFilterOptions,
            ],
            'resource_filter' => [
                'selected_house_id' => $resourceSelection['house_id'],
                'selected_pen_id' => $resourceSelection['pen_id'],
                'options' => $resourceFilterOptions,
            ],
            'gauges' => [
                [
                    'label' => 'Temperature',
                    'value' => $environmentReadings['temperature']['value'],
                    'unit' => 'deg',
                    'min' => 0,
                    'max' => 40,
                    'color' => '#6ABF4B',
                    'recorded_at' => $environmentReadings['temperature']['recorded_at'],
                ],
                [
                    'label' => 'Ammonia',
                    'value' => $environmentReadings['ammonia']['value'],
                    'unit' => 'ppm',
                    'min' => 0,
                    'max' => 40,
                    'color' => '#F4B43A',
                    'recorded_at' => $environmentReadings['ammonia']['recorded_at'],
                ],
            ],
            'resources' => [
                [
                    'label' => 'Feed',
                    'value' => $resourceReadings['feed']['value'],
                    'unit' => '%',
                    'max' => 100,
                    'color' => '#C88A3D',
                    'recorded_at' => $resourceReadings['feed']['recorded_at'],
                ],
                [
                    'label' => 'Water',
                    'value' => $resourceReadings['water']['value'],
                    'unit' => '%',
                    'max' => 100,
                    'color' => '#6CDDE5',
                    'recorded_at' => $resourceReadings['water']['recorded_at'],
                ],
            ],
            'pending_task_count' => $pendingTasks,
            'pending_task' => $pendingTask ? [
                'title' => $pendingTask->tasktype,
                'detail' => $pendingTask->detailedtask,
                'priority' => $pendingTask->prioritylevel,
                'finish_by' => $pendingTask->finishby
                    ? Carbon::parse($pendingTask->finishby)->format('M j, g:i A')
                    : null,
                'house_label' => $pendingTask->house_number,
                'pen_label' => $pendingTask->pen_name,
            ] : null,
            'quick_access' => [
                [
                    'title' => 'Population',
                    'icon_key' => 'population',
                    'tint' => '#D92C2C',
                    'action_key' => 'population',
                ],
                [
                    'title' => 'Feeds Refill',
                    'icon_key' => 'feeds',
                    'tint' => '#CC8A2D',
                    'action_key' => 'feeds_refill',
                ],
                [
                    'title' => 'Biosecurity',
                    'icon_key' => 'biosecurity',
                    'tint' => '#2F8F45',
                    'action_key' => 'biosecurity',
                ],
            ],
        ]);
    }

    private function buildSensorFilterOptions(array $sensorTypes): array
    {
        return DB::table('sensors as s')
            ->leftJoin('house as h', 's.house_houseid', '=', 'h.id')
            ->leftJoin('pen as p', function ($join) {
                $join->on('s.pen_penid', '=', 'p.id')
                    ->on('s.house_houseid', '=', 'p.house_id');
            })
            ->where(function ($query) use ($sensorTypes) {
                foreach ($sensorTypes as $type) {
                    if ($type === 'temperature') {
                        $query->orWhereRaw("LOWER(TRIM(s.sensortype)) LIKE ?", ['%temp%']);
                    }

                    if ($type === 'ammonia') {
                        $query->orWhereRaw("LOWER(TRIM(s.sensortype)) LIKE ?", ['%ammonia%'])
                            ->orWhereRaw("LOWER(TRIM(s.sensortype)) LIKE ?", ['%nh3%']);
                    }

                    if ($type === 'feed') {
                        $query->orWhereRaw("LOWER(TRIM(s.sensortype)) LIKE ?", ['%feed%']);
                    }

                    if ($type === 'water') {
                        $query->orWhereRaw("LOWER(TRIM(s.sensortype)) LIKE ?", ['%water%']);
                    }
                }
            })
            ->whereRaw("LOWER(TRIM(s.status)) = 'active'")
            ->whereNotNull('s.house_houseid')
            ->whereNotNull('s.pen_penid')
            ->select(
                's.house_houseid',
                's.pen_penid',
                'h.house_number',
                'p.pen_name'
            )
            ->distinct()
            ->orderBy('s.house_houseid')
            ->orderBy('s.pen_penid')
            ->get()
            ->map(function ($row) {
                return [
                    'house_id' => (int) $row->house_houseid,
                    'house_number' => $row->house_number ?: 'House ' . $row->house_houseid,
                    'pen_id' => (int) $row->pen_penid,
                    'pen_name' => $row->pen_name ?: 'Pen ' . $row->pen_penid,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveSelection(array $options, ?int $houseId, ?int $penId): array
    {
        if (empty($options)) {
            return [
                'house_id' => null,
                'pen_id' => null,
            ];
        }

        foreach ($options as $option) {
            if (
                (int) $option['house_id'] === (int) $houseId &&
                (int) $option['pen_id'] === (int) $penId
            ) {
                return [
                    'house_id' => (int) $option['house_id'],
                    'pen_id' => (int) $option['pen_id'],
                ];
            }
        }

        return [
            'house_id' => (int) $options[0]['house_id'],
            'pen_id' => (int) $options[0]['pen_id'],
        ];
    }

    private function latestReadingsByType(array $sensorTypes, ?int $houseId, ?int $penId): array
    {
        $defaults = [];
        foreach ($sensorTypes as $type) {
            $defaults[$type] = [
                'value' => 0,
                'recorded_at' => null,
            ];
        }

        if (!$houseId || !$penId) {
            return $defaults;
        }

        $rows = DB::table('sensors as s')
            ->join('sensor_readings as sr', 's.sensorid', '=', 'sr.sensorid')
            ->whereRaw("LOWER(TRIM(s.status)) = 'active'")
            ->where('s.house_houseid', $houseId)
            ->where('s.pen_penid', $penId)
            ->select(
                DB::raw("
                    CASE
                        WHEN LOWER(TRIM(s.sensortype)) LIKE '%temp%' THEN 'temperature'
                        WHEN LOWER(TRIM(s.sensortype)) LIKE '%ammonia%' THEN 'ammonia'
                        WHEN LOWER(TRIM(s.sensortype)) LIKE '%nh3%' THEN 'ammonia'
                        WHEN LOWER(TRIM(s.sensortype)) LIKE '%feed%' THEN 'feed'
                        WHEN LOWER(TRIM(s.sensortype)) LIKE '%water%' THEN 'water'
                        ELSE LOWER(TRIM(s.sensortype))
                    END as sensor_type
                "),
                'sr.value',
                'sr.recorded_at',
                'sr.reading_id'
            )
            ->orderByDesc('sr.recorded_at')
            ->orderByDesc('sr.reading_id')
            ->get()
            ->filter(function ($row) use ($sensorTypes) {
                return in_array($row->sensor_type, $sensorTypes, true);
            })
            ->groupBy('sensor_type');

        foreach ($sensorTypes as $type) {
            $latest = $rows[$type][0] ?? null;

            if ($latest) {
                $defaults[$type] = [
                    'value' => (float) $latest->value,
                    'recorded_at' => $latest->recorded_at
                        ? Carbon::parse($latest->recorded_at)->toDateTimeString()
                        : null,
                ];
            }
        }

        return $defaults;
    }
}