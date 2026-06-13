<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ManagementCreateTaskController;
use App\Http\Controllers\BiosecurityLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FarmActivityController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\LoginController;


Route::post('/api/login', [AuthController::class, 'login']);

// API endpoint to get current user info
Route::get('/api/user', [ProfileController::class, 'getCurrentUser']);

/*
|--------------------------------------------------------------------------
| WEB VIEWS
|--------------------------------------------------------------------------
*/

Route::get('/', [LoginController::class, 'show'])->name('login');

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| MANAGER VIEWS
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth.session', 'check.role:Manager', 'prevent.cache'])->group(function () {
    Route::get('/manager/dashboard', [ManagerDashboardController::class, 'index'])->name('manager.dashboard');
    Route::view('/manager/workers', 'manager.workers')->name('manager.workers');
    Route::view('/manager/houses', 'manager.houses')->name('manager.houses');

    Route::view('/manager/houses/records', 'manager.record-house')
        ->name('manager.houses.record-house');

    Route::get('/manager/inventory', [InventoryController::class, 'managerIndex'])
        ->name('manager.inventory');

    Route::view('/manager/inventory/records', 'manager.record-inventory')
    ->name('manager.inventory.records');

    Route::view('/manager/inventory/records/feed', 'manager.record-inventory-feed')
        ->name('manager.inventory.records.feed');

    Route::view('/manager/inventory/records/vitamins', 'manager.record-inventory')
        ->name('manager.inventory.records.vitamins');

    Route::view('/manager/tasks', 'manager.tasks')->name('manager.tasks');
    Route::view('/manager/management', 'manager.management')->name('manager.management');
    Route::view('/manager/farm-activity-records', 'manager.farm-activity-records')->name('manager.farm-activity-records');
    Route::get('/manager/profile', [ProfileController::class, 'managerProfile'])->name('manager.profile');
    Route::patch('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| ADMIN VIEWS
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth.session', 'check.role:Admin', 'prevent.cache'])->group(function () {
    Route::get('/admin/dashboard', [ManagerDashboardController::class, 'adminIndex'])->name('admin.dashboard');
    Route::view('/admin/workers', 'admin.workers')->name('admin.workers');
    Route::view('/admin/houses', 'admin.houses')->name('admin.houses');
    Route::get('/admin/profile', [ProfileController::class, 'adminProfile'])->name('admin.profile');

    Route::view('/admin/houses/records', 'admin.record-house')
        ->name('admin.houses.record-house');
});

/*
|--------------------------------------------------------------------------
| MANAGER API (MOCK DATA - KEEP)
|--------------------------------------------------------------------------
*/

if (! function_exists('dashboardMonitoringGraphsData')) {
    function dashboardMonitoringGraphsData(): array
    {
        $days = collect(range(6, 0))->map(fn ($offset) => now()->subDays($offset)->startOfDay());
        $dayKeys = $days->map(fn ($day) => $day->toDateString())->all();
        $dayLabels = $days->map(fn ($day) => $day->format('D'))->all();
        $seriesColors = ['#17643a', '#b7791f', '#2563eb', '#a855f7', '#dc2626', '#0891b2', '#4d7c0f', '#be185d'];

        $houses = \App\Models\House::query()
            ->whereNull('archived_at')
            ->orderBy('id', 'asc')
            ->get(['id', 'house_number']);

        $penBuckets = [];

        if ($houses->isNotEmpty()) {
            $rows = DB::table('sensor_readings as sr')
                ->join('sensors as s', 'sr.sensorid', '=', 's.sensorid')
                ->join('house as h', 's.house_houseid', '=', 'h.id')
                ->join('pen as p', function ($join) {
                    $join->on('s.pen_penid', '=', 'p.id')
                        ->on('s.house_houseid', '=', 'p.house_id');
                })
                ->whereRaw("LOWER(TRIM(s.status)) = 'active'")
                ->whereNull('h.archived_at')
                ->whereNull('p.archived_at')
                ->whereNotNull('s.pen_penid')
                ->whereBetween('sr.recorded_at', [$days->first()->copy()->startOfDay(), $days->last()->copy()->endOfDay()])
                ->select('s.house_houseid', 's.pen_penid', 's.sensortype', 'sr.value', 'sr.recorded_at')
                ->get();

            foreach ($rows as $row) {
                $sensorType = strtolower(trim((string) $row->sensortype));

                if (str_contains($sensorType, 'temp')) {
                    $type = 'temperature';
                } elseif (str_contains($sensorType, 'ammonia') || str_contains($sensorType, 'nh3')) {
                    $type = 'ammonia';
                } else {
                    continue;
                }

                $dayKey = \Illuminate\Support\Carbon::parse($row->recorded_at)->toDateString();

                if (! in_array($dayKey, $dayKeys, true)) {
                    continue;
                }

                $houseId = (int) $row->house_houseid;
                $penId = (int) $row->pen_penid;
                $value = (float) $row->value;

                if (! isset($penBuckets[$type][$houseId][$dayKey][$penId])) {
                    $penBuckets[$type][$houseId][$dayKey][$penId] = ['total' => 0, 'count' => 0];
                }

                $penBuckets[$type][$houseId][$dayKey][$penId]['total'] += $value;
                $penBuckets[$type][$houseId][$dayKey][$penId]['count']++;
            }
        }

        $buildDatasets = function (string $type) use ($houses, $dayKeys, $seriesColors, $penBuckets) {
            return $houses->values()->map(function ($house, $index) use ($type, $dayKeys, $seriesColors, $penBuckets) {
                $color = $seriesColors[$index % count($seriesColors)];

                $values = array_map(function ($dayKey) use ($type, $house, $penBuckets) {
                    $penAverages = [];

                    foreach (($penBuckets[$type][(int) $house->id][$dayKey] ?? []) as $bucket) {
                        if (($bucket['count'] ?? 0) > 0) {
                            $penAverages[] = $bucket['total'] / $bucket['count'];
                        }
                    }

                    return count($penAverages) > 0
                        ? round(array_sum($penAverages) / count($penAverages), 1)
                        : 0;
                }, $dayKeys);

                return [
                    'label' => $house->house_number,
                    'data' => $values,
                    'borderColor' => $color,
                    'backgroundColor' => $color,
                    'hoverBackgroundColor' => $color,
                    'borderWidth' => 0,
                    'borderRadius' => 10,
                    'borderSkipped' => false,
                    'maxBarThickness' => 28,
                ];
            })->all();
        };

        return [
            'slides' => [
                [
                    'label' => 'Temperature',
                    'unit' => 'deg',
                    'labels' => $dayLabels,
                    'datasets' => $buildDatasets('temperature'),
                    'borderColor' => '#17643a',
                    'backgroundColor' => 'rgba(23, 100, 58, 0.72)',
                ],
                [
                    'label' => 'Ammonia',
                    'unit' => 'ppm',
                    'labels' => $dayLabels,
                    'datasets' => $buildDatasets('ammonia'),
                    'borderColor' => '#b7791f',
                    'backgroundColor' => 'rgba(183, 121, 31, 0.72)',
                ],
            ],
        ];
    }
}

Route::get('/api/manager/dashboard/monitoring-graphs', function () {
    return response()->json(dashboardMonitoringGraphsData());
});

Route::get('/api/manager/dashboard/realtime', function () {
    return response()->json([
        'environment' => [
            ['label' => 'Temperature', 'value' => 24, 'unit' => 'deg', 'min' => 0, 'max' => 35, 'status' => 'safe'],
            ['label' => 'Ammonia', 'value' => 7, 'unit' => 'ppm', 'min' => 0, 'max' => 30, 'status' => 'warning'],
        ],
        'resources' => [
            ['label' => 'Feed', 'value' => 60, 'unit' => '%', 'type' => 'feed'],
            ['label' => 'Water', 'value' => 30, 'unit' => '%', 'type' => 'water'],
        ],
    ]);
});

Route::get('/api/manager/dashboard/environment-by-house', function () {
    try {
        $houses = \App\Models\House::with([
            'pens' => function ($query) {
                $query->whereNull('archived_at');
            }
        ])
            ->whereNull('archived_at')
            ->orderBy('id', 'asc')
            ->get();

        // Use the HouseController's logic to attach sensor readings
        $controller = new \App\Http\Controllers\HouseController();
        $reflectionMethod = new \ReflectionMethod($controller, 'attachLatestSensorReadings');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($controller, $houses);

        $temperatureByHouse = [];
        $ammoniaByHouse = [];

        foreach ($houses as $house) {
            $temperatureReadings = [];
            $ammoniaReadings = [];

            foreach ($house->pens as $pen) {
                $sensorReadings = $pen->getAttribute('sensor_readings');
                if ($sensorReadings) {
                    if (isset($sensorReadings['temperature']) && $sensorReadings['temperature']) {
                        $temperatureReadings[] = (float) $sensorReadings['temperature']['value'];
                    }
                    if (isset($sensorReadings['ammonia']) && $sensorReadings['ammonia']) {
                        $ammoniaReadings[] = (float) $sensorReadings['ammonia']['value'];
                    }
                }
            }

            $avgTemp = count($temperatureReadings) > 0 ? array_sum($temperatureReadings) / count($temperatureReadings) : 0;
            $avgAmmonia = count($ammoniaReadings) > 0 ? array_sum($ammoniaReadings) / count($ammoniaReadings) : 0;

            $temperatureByHouse[] = round($avgTemp, 1);
            $ammoniaByHouse[] = round($avgAmmonia, 1);
        }

        $houseLabels = $houses->pluck('house_number')->toArray();

        return response()->json([
            'slides' => [
                [
                    'label' => 'Temperature',
                    'unit' => 'deg',
                    'labels' => $houseLabels,
                    'values' => $temperatureByHouse,
                    'borderColor' => '#17643a',
                    'backgroundColor' => 'rgba(23, 100, 58, 0.72)',
                    'maxValue' => 35,
                ],
                [
                    'label' => 'Ammonia',
                    'unit' => 'ppm',
                    'labels' => $houseLabels,
                    'values' => $ammoniaByHouse,
                    'borderColor' => '#b7791f',
                    'backgroundColor' => 'rgba(183, 121, 31, 0.72)',
                    'maxValue' => 25,
                ],
            ],
        ]);
    } catch (\Exception $e) {
        \Log::error('Environment by house error: ' . $e->getMessage());
        return response()->json([
            'slides' => [
                [
                    'label' => 'Temperature',
                    'unit' => 'deg',
                    'labels' => ['House 1', 'House 2'],
                    'values' => [24, 23],
                    'borderColor' => '#17643a',
                    'backgroundColor' => 'rgba(23, 100, 58, 0.72)',
                    'maxValue' => 35,
                ],
                [
                    'label' => 'Ammonia',
                    'unit' => 'ppm',
                    'labels' => ['House 1', 'House 2'],
                    'values' => [8, 10],
                    'borderColor' => '#b7791f',
                    'backgroundColor' => 'rgba(183, 121, 31, 0.72)',
                    'maxValue' => 25,
                ],
            ],
        ]);
    }
});

Route::get('/api/manager/dashboard/resources-by-house', function () {
    try {
        $houses = \App\Models\House::with([
            'pens' => function ($query) {
                $query->whereNull('archived_at');
            }
        ])
            ->whereNull('archived_at')
            ->orderBy('id', 'asc')
            ->get();

        // Use the HouseController's logic to attach sensor readings
        $controller = new \App\Http\Controllers\HouseController();
        $reflectionMethod = new \ReflectionMethod($controller, 'attachLatestSensorReadings');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($controller, $houses);

        $feedByHouse = [];
        $waterByHouse = [];

        foreach ($houses as $house) {
            $feedReadings = [];
            $waterReadings = [];

            foreach ($house->pens as $pen) {
                $sensorReadings = $pen->getAttribute('sensor_readings');
                if ($sensorReadings) {
                    // Get feeders
                    if (isset($sensorReadings['feeders']) && is_array($sensorReadings['feeders'])) {
                        foreach ($sensorReadings['feeders'] as $feeder) {
                            if ($feeder && isset($feeder['value'])) {
                                $feedReadings[] = (float) $feeder['value'];
                            }
                        }
                    }
                    // Get drinkers
                    if (isset($sensorReadings['drinkers']) && is_array($sensorReadings['drinkers'])) {
                        foreach ($sensorReadings['drinkers'] as $drinker) {
                            if ($drinker && isset($drinker['value'])) {
                                $waterReadings[] = (float) $drinker['value'];
                            }
                        }
                    }
                }
            }

            $avgFeed = count($feedReadings) > 0 ? array_sum($feedReadings) / count($feedReadings) : 0;
            $avgWater = count($waterReadings) > 0 ? array_sum($waterReadings) / count($waterReadings) : 0;

            $feedByHouse[] = round($avgFeed, 1);
            $waterByHouse[] = round($avgWater, 1);
        }

        $houseLabels = $houses->pluck('house_number')->toArray();

        return response()->json([
            'slides' => [
                [
                    'label' => 'Feed',
                    'unit' => '%',
                    'labels' => $houseLabels,
                    'values' => $feedByHouse,
                    'borderColor' => '#c88a3d',
                    'backgroundColor' => 'rgba(200, 138, 61, 0.72)',
                    'maxValue' => 100,
                ],
                [
                    'label' => 'Water',
                    'unit' => '%',
                    'labels' => $houseLabels,
                    'values' => $waterByHouse,
                    'borderColor' => '#6cdde5',
                    'backgroundColor' => 'rgba(108, 221, 229, 0.72)',
                    'maxValue' => 100,
                ],
            ],
        ]);
    } catch (\Exception $e) {
        \Log::error('Resources by house error: ' . $e->getMessage());
        return response()->json([
            'slides' => [
                [
                    'label' => 'Feed',
                    'unit' => '%',
                    'labels' => ['House 1', 'House 2'],
                    'values' => [60, 65],
                    'borderColor' => '#c88a3d',
                    'backgroundColor' => 'rgba(200, 138, 61, 0.72)',
                    'maxValue' => 100,
                ],
                [
                    'label' => 'Water',
                    'unit' => '%',
                    'labels' => ['House 1', 'House 2'],
                    'values' => [45, 50],
                    'borderColor' => '#6cdde5',
                    'backgroundColor' => 'rgba(108, 221, 229, 0.72)',
                    'maxValue' => 100,
                ],
            ],
        ]);
    }
});

Route::get('/api/manager/dashboard/decision-support', function (\Illuminate\Http\Request $request) {
    $service = new \App\Services\DecisionSupportService();
    $generated = $service->generateRecommendations(null, $request->boolean('refresh'));
    $recommendations = $service->getAllActiveRecommendations();
    
    return response()->json([
        'generated' => $generated,
        'recommendations' => $recommendations->map(function ($rec) {
            $ruleDecisions = collect($rec->data_snapshot['rule_decisions'] ?? []);
            $severityRank = [
                'normal' => 1,
                'warning' => 2,
                'critical' => 3,
            ];
            $severity = $ruleDecisions
                ->pluck('severity')
                ->sortByDesc(fn ($value) => $severityRank[$value] ?? 0)
                ->first() ?? 'normal';

            return [
                'id' => $rec->id,
                'house_id' => $rec->house_id,
                'house_name' => $rec->house?->house_number ?? "House {$rec->house_id}",
                'pen_id' => $rec->pen_id,
                'pen_name' => $rec->pen?->pen_name ?? ($rec->pen_id ? "Pen {$rec->pen_id}" : null),
                'text' => $rec->recommendation_text,
                'severity' => $severity,
                'categories' => $ruleDecisions->pluck('category')->unique()->values()->all(),
                'findings_count' => $ruleDecisions->count(),
                'generated_at' => $rec->generated_at->toIso8601String(),
                'expires_at' => $rec->expires_at?->toIso8601String(),
            ];
        })->toArray(),
    ]);
});

Route::get('/api/manager/workers', function () {
    return response()->json([
        [
            'id' => 1,
            'first_name' => 'Juan',
            'middle_name' => 'Fransis',
            'last_name' => 'Dela Cruz',
            'name' => 'Juan Dela Cruz',
            'role' => 'Manager',
            'phone' => '09218729021',
            'birthday' => '07/24/1993',
            'gender' => 'Male',
            'address' => 'Sitio Burol Lucban, Quezon',
        ],
    ]);
});

/*
|--------------------------------------------------------------------------
| ADMIN API
|--------------------------------------------------------------------------
*/

Route::get('/api/admin/dashboard/realtime', function () {
    return response()->json([
        'environment' => [
            ['label' => 'Temperature', 'value' => 24, 'unit' => 'deg', 'min' => 0, 'max' => 35, 'status' => 'safe'],
            ['label' => 'Ammonia', 'value' => 7, 'unit' => 'ppm', 'min' => 0, 'max' => 30, 'status' => 'warning'],
        ],
        'resources' => [
            ['label' => 'Feed', 'value' => 60, 'unit' => '%', 'type' => 'feed'],
            ['label' => 'Water', 'value' => 30, 'unit' => '%', 'type' => 'water'],
        ],
    ]);
});

Route::get('/api/admin/dashboard/monitoring-graphs', function () {
    return response()->json(dashboardMonitoringGraphsData());
});

Route::get('/api/admin/dashboard/environment-by-house', function () {
    return redirect('/api/manager/dashboard/environment-by-house');
});

Route::get('/api/admin/dashboard/resources-by-house', function () {
    return redirect('/api/manager/dashboard/resources-by-house');
});

/*
|--------------------------------------------------------------------------
| ADMIN WORKERS CRUD
|--------------------------------------------------------------------------
*/

Route::prefix('api/admin/workers')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);
    Route::post('/', [EmployeeController::class, 'store']);
    Route::get('/{id}', [EmployeeController::class, 'show']);
    Route::put('/{id}', [EmployeeController::class, 'update']);
    Route::delete('/{id}', [EmployeeController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| ✅ MANAGER INVENTORY CRUD (UPDATED)
|--------------------------------------------------------------------------
*/

Route::prefix('api/manager/inventory')->group(function () {
    Route::get('/snapshot', [InventoryController::class, 'snapshot']);
    Route::post('/', [InventoryController::class, 'store']);
    Route::put('/{id}', [InventoryController::class, 'update']);
    Route::delete('/{id}', [InventoryController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| ✅ INVENTORY FETCH (FIXED)
|--------------------------------------------------------------------------
|
| Use:
| /api/inventory-items?type=feed
| /api/inventory-items?type=vitamin
|
*/

Route::get('/api/inventory-items', [InventoryController::class, 'items']);
Route::get('/api/inventory-records', [InventoryController::class, 'records']);
Route::post('/api/manager/inventory/types', [ManagementCreateTaskController::class, 'storeInventoryType']);

/*
|--------------------------------------------------------------------------
| ✅ HOUSE MANAGEMENT CRUD
|--------------------------------------------------------------------------
*/

Route::prefix('api/houses')->group(function () {
    Route::get('/', [HouseController::class, 'index']);              // Get all houses
    Route::get('/records/pens', [HouseController::class, 'getPenRecords']); // Get pen records for all houses
    Route::post('/', [HouseController::class, 'store']);             // Create new house
    Route::delete('/pen/{penId}', [HouseController::class, 'deletePen']); // Delete specific pen (must be before /{id})
    Route::post('/{id}/archive', [HouseController::class, 'archive']); // Soft archive house
    Route::get('/{id}', [HouseController::class, 'show']);           // Get specific house
    Route::put('/{id}', [HouseController::class, 'update']);         // Update house
    Route::delete('/{id}', [HouseController::class, 'destroy']);     // Delete house
});

// Pen data routes
Route::prefix('api/pens')->group(function () {
    Route::put('/{penId}', [HouseController::class, 'updatePen']);              // Update pen capacity/population
    Route::put('/{penId}/production', [HouseController::class, 'updatePenProduction']); // Update pen production data
});

/*
|--------------------------------------------------------------------------
| TASKS API
|--------------------------------------------------------------------------
*/

Route::get('/api/manager/tasks', [TaskController::class, 'index']);
Route::post('/api/manager/tasks', [TaskController::class, 'store']);
Route::post('/api/manager/tasks/types', [ManagementCreateTaskController::class, 'storeTaskType']);
Route::put('/api/manager/tasks/{taskId}', [TaskController::class, 'update']);
Route::delete('/api/manager/tasks/{taskId}', [TaskController::class, 'destroy']);
Route::get('/api/manager/tasks/form-options', [TaskController::class, 'formOptions']);
Route::get('/api/manager/tasks/houses/{houseId}/pens', [TaskController::class, 'getPensForHouse']);
Route::get('/api/manager/tasks/all-workers', [TaskController::class, 'getAllWorkers']);

/*
|--------------------------------------------------------------------------
| SENSOR PLACEHOLDERS
|--------------------------------------------------------------------------
*/

Route::view('/manager/sensors', 'manager.sensors')->name('manager.sensors');

Route::view('/manager/sensors', 'manager.sensors')->name('manager.sensors');
Route::view('/manager/sensors/maintenance-records', 'manager.sensor-maintenance')->name('manager.sensor-maintenance');

Route::get('/api/manager/sensors', [SensorController::class, 'index']);
Route::get('/api/manager/sensors/readings', [SensorController::class, 'sensorReadings']);
Route::get('/api/manager/sensors/health', [SensorController::class, 'sensorHealth']);

Route::get('/api/manager/sensors/maintenance-records', [SensorController::class, 'managerMaintenanceRecords']);

Route::view('/admin/sensors', 'admin.sensors')->name('admin.sensors');
Route::view('/admin/sensors/maintenance-records', 'admin.sensor-maintenance')->name('admin.sensor-maintenance');

Route::prefix('api/admin/sensors')->group(function () {
    Route::get('/', [SensorController::class, 'index']);
    Route::get('/readings', [SensorController::class, 'sensorReadings']);
    Route::get('/health', [SensorController::class, 'sensorHealth']);
    Route::get('/form-options', [SensorController::class, 'formOptions']);
    Route::get('/houses/{houseId}/pens', [SensorController::class, 'getPensForHouse']);
    Route::get('/pens/{penId}/available-feeders', [SensorController::class, 'getAvailableFeedersForPen']);
    Route::get('/pens/{penId}/available-drinkers', [SensorController::class, 'getAvailableDrinkersForPen']);
    Route::post('/', [SensorController::class, 'store']);
    Route::put('/thresholds', [SensorController::class, 'updateThresholds']);
    Route::patch('/{sensorId}/status', [SensorController::class, 'updateStatus']);
    Route::put('/{sensorId}', [SensorController::class, 'update']);
    Route::delete('/{sensorId}', [SensorController::class, 'destroy']);
});

Route::get('/api/admin/sensors/maintenance-records', [SensorController::class, 'adminMaintenanceRecords']);

Route::view('/manager/biosecurity-logs', 'manager.biosecurity-logs')
    ->name('manager.biosecurity-logs');

/*
|--------------------------------------------------------------------------
| BIOSECURITY LOGS API
|--------------------------------------------------------------------------
*/

Route::get('/api/manager/biosecurity-logs', [BiosecurityLogController::class, 'index']);
Route::post('/api/manager/biosecurity-logs', [BiosecurityLogController::class, 'store']);
Route::post('/api/manager/biosecurity-logs/visitor-photo', [BiosecurityLogController::class, 'uploadVisitorPhoto']);
Route::put('/api/manager/biosecurity-logs/{id}', [BiosecurityLogController::class, 'update']);
Route::delete('/api/manager/biosecurity-logs/{id}', [BiosecurityLogController::class, 'destroy']);

Route::view('/manager/reports', 'manager.reports')->name('manager.reports');
Route::get('/api/manager/reports', [ReportsController::class, 'index']);
Route::get('/manager/reports/generate', [ReportsController::class, 'generate'])->name('manager.reports.generate');

Route::get('/api/manager/farm-activity/pens', [FarmActivityController::class, 'pens']);
Route::get('/api/manager/farm-activity/weight-sampling-logs', [FarmActivityController::class, 'weightSamplingLogs']);
Route::get('/api/manager/farm-activity/feed-refill-records', [FarmActivityController::class, 'feedRefillRecords']);
Route::get('/api/manager/farm-activity/vitamin-refill-records', [FarmActivityController::class, 'vitaminRefillRecords']);
Route::get('/api/manager/farm-activity/cleaning-logs', [FarmActivityController::class, 'cleaningLogs']);
Route::get('/api/manager/farm-activity/sensor-inspection-logs', [FarmActivityController::class, 'sensorInspectionLogs']);
Route::get('/api/manager/farm-activity/flock-batches', [FarmActivityController::class, 'flockBatches']);
