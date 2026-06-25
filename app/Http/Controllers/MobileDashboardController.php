<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileDashboardController extends Controller
{
    public function show(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $validated = $request->validate([
            'environment_house_id' => 'nullable|integer',
            'environment_pen_id' => 'nullable|integer',
            'resource_house_id' => 'nullable|integer',
            'resource_pen_id' => 'nullable|integer',
        ]);

        $validPopulationRecords = DB::table('population_record as pr')
            ->join('pen as p', 'pr.pen_id', '=', 'p.id')
            ->join('house as h', 'p.house_id', '=', 'h.id')
            ->join('flock_batches as fb', 'p.current_batch_id', '=', 'fb.id')
            ->whereNull('h.archived_at')
            ->whereNull('p.archived_at')
            ->whereRaw("LOWER(TRIM(fb.status)) = 'running'")
            ->whereNull('fb.ended_at')
            ->whereColumn('fb.house_id', 'p.house_id')
            ->whereColumn('fb.pen_id', 'p.id')
            ->whereColumn('fb.id', 'p.current_batch_id')
            ->whereRaw('pr.recorded_at >= fb.started_at');

        $latestPopulationDate = (clone $validPopulationRecords)
            ->selectRaw('DATE(pr.recorded_at) as record_date')
            ->orderByRaw('DATE(pr.recorded_at) desc')
            ->value('record_date');

        $dailyEggs = 0;
        $dailyMortalities = 0;
        $overviewDateLabel = Carbon::now()->format('n/j/y');

        if (!empty($latestPopulationDate)) {
            $dailyTotals = (clone $validPopulationRecords)
                ->selectRaw('COALESCE(SUM(pr.eggs_hatched), 0) as total_eggs')
                ->selectRaw('COALESCE(SUM(pr.mortality), 0) as total_mortalities')
                ->whereDate('pr.recorded_at', $latestPopulationDate)
                ->first();

            $dailyEggs = (int) ($dailyTotals->total_eggs ?? 0);
            $dailyMortalities = (int) ($dailyTotals->total_mortalities ?? 0);
            $overviewDateLabel = Carbon::parse($latestPopulationDate)->format('n/j/y');
        }

        $totalBirds = (int) DB::table('pen as p')
            ->join('house as h', 'p.house_id', '=', 'h.id')
            ->join('flock_batches as fb', 'p.current_batch_id', '=', 'fb.id')
            ->whereNull('h.archived_at')
            ->whereNull('p.archived_at')
            ->whereRaw("LOWER(TRIM(fb.status)) = 'running'")
            ->whereNull('fb.ended_at')
            ->whereColumn('fb.house_id', 'p.house_id')
            ->whereColumn('fb.pen_id', 'p.id')
            ->whereColumn('fb.id', 'p.current_batch_id')
            ->sum(DB::raw('COALESCE(p.population, 0)'));

        $pendingTasks = (int) DB::table('tasks')
            ->where('user_employeeid', $employeeId)
            ->where('status', 'Pending')
            ->count();

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
                    'title' => 'Total Chickens',
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
                    'max' => $environmentReadings['temperature']['highest_threshold'],
                    'color' => '#6ABF4B',
                    'recorded_at' => $environmentReadings['temperature']['recorded_at'],
                ],
                [
                    'label' => 'Ammonia',
                    'value' => $environmentReadings['ammonia']['value'],
                    'unit' => 'ppm',
                    'min' => 0,
                    'max' => $environmentReadings['ammonia']['highest_threshold'],
                    'color' => '#F4B43A',
                    'recorded_at' => $environmentReadings['ammonia']['recorded_at'],
                ],
            ],
            'resources' => $this->latestResourceReadings(
                $resourceSelection['house_id'],
                $resourceSelection['pen_id']
            ),
            'pending_task_count' => $pendingTasks,
            'pending_task' => null,
            'quick_access' => [
                [
                    'title' => 'Visitor Log',
                    'icon_key' => 'visitor',
                    'tint' => '#2E7D6B',
                    'action_key' => 'visitor',
                ],
            ],
        ]);
    }

    private function buildSensorFilterOptions(array $sensorTypes): array
    {
        return DB::table('sensors as s')
            ->join('house as h', 's.house_houseid', '=', 'h.id')
            ->join('pen as p', function ($join) {
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
            ->whereNull('h.archived_at')
            ->whereNull('p.archived_at')
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

    private function latestResourceReadings(?int $houseId, ?int $penId): array
    {
        if (!$houseId || !$penId) {
            return [];
        }

        $containerHeightInches = 8.5;

        $latestReadingIds = DB::table('sensor_readings')
            ->selectRaw('sensorid, max(reading_id) as latest_reading_id')
            ->groupBy('sensorid');

        return DB::table('sensors as s')
            ->leftJoinSub($latestReadingIds, 'latest', function ($join) {
                $join->on('s.sensorid', '=', 'latest.sensorid');
            })
            ->leftJoin('sensor_readings as sr', 'sr.reading_id', '=', 'latest.latest_reading_id')
            ->whereRaw("LOWER(TRIM(s.status)) = 'active'")
            ->where('s.house_houseid', $houseId)
            ->where('s.pen_penid', $penId)
            ->where(function ($query) {
                $query->whereRaw("LOWER(TRIM(s.sensortype)) LIKE ?", ['%feed%'])
                    ->orWhereRaw("LOWER(TRIM(s.sensortype)) LIKE ?", ['%water%']);
            })
            ->select(
                's.sensorid',
                's.sensorname',
                's.sensortype',
                's.feeder_number',
                's.drinker_number',
                'sr.value',
                'sr.recorded_at',
                'sr.reading_id'
            )
            ->orderByRaw("
            CASE
                WHEN LOWER(TRIM(s.sensortype)) LIKE '%feed%' THEN 1
                WHEN LOWER(TRIM(s.sensortype)) LIKE '%water%' THEN 2
                ELSE 3
            END
        ")
            ->orderBy('s.feeder_number')
            ->orderBy('s.drinker_number')
            ->orderBy('s.sensorid')
            ->get()
            ->map(function ($row) use ($containerHeightInches) {
                $sensorType = strtolower(trim((string) $row->sensortype));
                $isFeed = str_contains($sensorType, 'feed');
                $isWater = str_contains($sensorType, 'water');

                if ($isFeed) {
                    $label = !empty($row->feeder_number)
                        ? 'Feeder ' . $row->feeder_number
                        : ($row->sensorname ?: 'Feed');
                } elseif ($isWater) {
                    $label = !empty($row->drinker_number)
                        ? 'Drinker ' . $row->drinker_number
                        : ($row->sensorname ?: 'Water');
                } else {
                    $label = $row->sensorname ?: 'Resource';
                }

                $rawInches = (float) ($row->value ?? 0);

                $percent = $containerHeightInches > 0
                    ? (($containerHeightInches - $rawInches) / $containerHeightInches) * 100
                    : 0;

                $percent = round(max(0, min(100, $percent)), 1);

                return [
                    'label' => $label,
                    'value' => $percent,
                    'unit' => '%',
                    'max' => 100,
                    'color' => $isFeed ? '#C88A3D' : '#3EA7B3',
                    'recorded_at' => $row->recorded_at
                        ? Carbon::parse($row->recorded_at)->toDateTimeString()
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    private function latestReadingsByType(array $sensorTypes, ?int $houseId, ?int $penId): array
    {
        $defaults = [];

        foreach ($sensorTypes as $type) {
            $defaults[$type] = [
                'value' => 0,
                'recorded_at' => null,
                'highest_threshold' => 40,
            ];
        }

        if (!$houseId || !$penId) {
            return $defaults;
        }

        $rows = DB::table('sensors as s')
            ->leftJoin('sensorconfigurations as sc', 'sc.sensors_sensorid', '=', 's.sensorid')
            ->leftJoin('sensor_readings as sr', 's.sensorid', '=', 'sr.sensorid')
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
                'sr.reading_id',
                'sc.highestthreshold'
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
                    'value' => (float) ($latest->value ?? 0),
                    'recorded_at' => $latest->recorded_at
                        ? Carbon::parse($latest->recorded_at)->toDateTimeString()
                        : null,
                    'highest_threshold' => (float) ($latest->highestthreshold ?? 40),
                ];
            }
        }

        return $defaults;
    }
}
