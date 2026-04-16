<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\BiosecurityLogController;
use App\Http\Controllers\ProfileController;

Route::post('/api/login', [AuthController::class, 'login']);

// API endpoint to get current user info
Route::get('/api/user', [ProfileController::class, 'getCurrentUser']);

/*
|--------------------------------------------------------------------------
| WEB VIEWS
|--------------------------------------------------------------------------
*/

Route::view('/', 'auth.login')->name('login');

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| MANAGER VIEWS
|--------------------------------------------------------------------------
*/

Route::view('/manager/dashboard', 'manager.dashboard')->name('manager.dashboard');
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

/*
|--------------------------------------------------------------------------
| ADMIN VIEWS
|--------------------------------------------------------------------------
*/

Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
Route::view('/admin/workers', 'admin.workers')->name('admin.workers');
Route::view('/admin/houses', 'admin.houses')->name('admin.houses');

Route::view('/admin/houses/records', 'admin.record-house')
    ->name('admin.houses.record-house');

/*
|--------------------------------------------------------------------------
| MANAGER API (MOCK DATA - KEEP)
|--------------------------------------------------------------------------
*/

Route::get('/api/manager/dashboard/monitoring-graphs', function () {
    return response()->json([
        'slides' => [
            [
                'label' => 'Temperature',
                'unit' => 'deg',
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'values' => [22, 24, 23, 25, 24, 26, 24],
                'borderColor' => '#63d7e6',
                'backgroundColor' => 'rgba(99, 215, 230, 0.16)',
            ],
            [
                'label' => 'Ammonia',
                'unit' => 'ppm',
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'values' => [12, 14, 13, 15, 17, 16, 15],
                'borderColor' => '#f1ab3c',
                'backgroundColor' => 'rgba(241, 171, 60, 0.18)',
            ],
        ],
    ]);
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
            ['label' => 'Temperature', 'value' => 24, 'unit' => 'deg', 'min' => 0, 'max' => 50, 'status' => 'safe'],
            ['label' => 'Ammonia', 'value' => 15, 'unit' => 'ppm', 'min' => 0, 'max' => 50, 'status' => 'warning'],
        ],
        'resources' => [
            ['label' => 'Feed', 'value' => 60, 'unit' => '%', 'type' => 'feed'],
            ['label' => 'Water', 'value' => 30, 'unit' => '%', 'type' => 'water'],
        ],
    ]);
});

Route::get('/api/admin/dashboard/monitoring-graphs', function () {
    return response()->json([
        'slides' => [],
    ]);
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

/*
|--------------------------------------------------------------------------
| ✅ HOUSE MANAGEMENT CRUD
|--------------------------------------------------------------------------
*/

Route::prefix('api/houses')->group(function () {
    Route::get('/', [HouseController::class, 'index']);              // Get all houses
    Route::get('/records/pens', [HouseController::class, 'getPenRecords']); // Get pen records for all houses
    Route::post('/', [HouseController::class, 'store']);             // Create new house
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
Route::put('/api/manager/tasks/{taskId}', [TaskController::class, 'update']);
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

Route::get('/api/manager/sensors/maintenance-records', [SensorController::class, 'managerMaintenanceRecords']);

Route::view('/admin/sensors', 'admin.sensors')->name('admin.sensors');
Route::view('/admin/sensors/maintenance-records', 'admin.sensor-maintenance')->name('admin.sensor-maintenance');

Route::prefix('api/admin/sensors')->group(function () {
    Route::get('/', [SensorController::class, 'index']);
    Route::get('/form-options', [SensorController::class, 'formOptions']);
    Route::get('/houses/{houseId}/pens', [SensorController::class, 'getPensForHouse']);
    Route::post('/', [SensorController::class, 'store']);
    Route::put('/thresholds', [SensorController::class, 'updateThresholds']);
    Route::patch('/{sensorId}/status', [SensorController::class, 'updateStatus']);
    Route::put('/{sensorId}', [SensorController::class, 'update']);
    Route::delete('/{sensorId}', [SensorController::class, 'destroy']);
});

Route::get('/api/admin/sensors/maintenance-records', function () {
    return response()->json([
        'records' => [
            [
                'sensor_type' => 'Temperature',
                'name' => 'Sensor 1',
                'house_number' => '1',
                'start_date' => '08-11-25',
                'end_date' => '08-13-25',
                'status' => 'Maintenance',
            ],
            [
                'sensor_type' => 'Ammonia',
                'name' => 'Sensor 5',
                'house_number' => '2',
                'start_date' => '07-24-25',
                'end_date' => '07-27-25',
                'status' => 'Maintenance',
            ],
        ],
    ]);
});

Route::view('/manager/biosecurity-logs', 'manager.biosecurity-logs')
    ->name('manager.biosecurity-logs');

/*
|--------------------------------------------------------------------------
| BIOSECURITY LOGS API
|--------------------------------------------------------------------------
*/

Route::get('/api/manager/biosecurity-logs', [BiosecurityLogController::class, 'index']);
Route::post('/api/manager/biosecurity-logs', [BiosecurityLogController::class, 'store']);
Route::put('/api/manager/biosecurity-logs/{id}', [BiosecurityLogController::class, 'update']);
Route::delete('/api/manager/biosecurity-logs/{id}', [BiosecurityLogController::class, 'destroy']);

Route::view('/manager/reports', 'manager.reports')->name('manager.reports');

Route::get('/api/manager/reports', function () {
    return response()->json([
        'reports' => [

            // ✅ POPULATION
            'Population' => [
                [
                    'batch_id' => 'Batch-2026-01',
                    'start_date' => '1-21-26',
                    'end_date' => '2-21-26',
                    'reporting_date' => '1-27-26',
                    'pen_no' => '1',
                    'initial_population' => '500',
                    'running_population' => '485',
                    'mortalities' => '15',
                    'eggs_hatched' => '0',
                ],
                [
                    'batch_id' => 'Batch-2026-02',
                    'start_date' => '1-21-26',
                    'end_date' => '2-21-26',
                    'reporting_date' => '1-27-26',
                    'pen_no' => '2',
                    'initial_population' => '500',
                    'running_population' => '490',
                    'mortalities' => '10',
                    'eggs_hatched' => '0',
                ],
            ],

            // ✅ ENVIRONMENTAL
            'Environmental' => [
                'temperature' => [
                    [
                        'batch' => 'Batch-2026-02',
                        'date' => '1-27-26',
                        'lowest_reading' => '29',
                        'highest_reading' => '33',
                        'average_reading' => '31',
                        'threshold_violations' => '2',
                        'house' => '1',
                    ],
                ],
                'ammonia' => [
                    [
                        'batch' => 'Batch-2026-02',
                        'date' => '1-27-26',
                        'lowest_reading' => '10',
                        'highest_reading' => '18',
                        'average_reading' => '14',
                        'threshold_violations' => '1',
                        'house' => '1',
                    ],
                ],
                'feeds' => [
                    [
                        'batch' => 'Batch-2026-02',
                        'date' => '1-27-26',
                        'feeds_level' => '75%',
                        'house' => '1',
                        'pen' => '2',
                        'feeder_number' => '3',
                    ],
                ],
                'water' => [
                    [
                        'batch' => 'Batch-2026-02',
                        'date' => '1-27-26',
                        'water_level' => '80%',
                        'house' => '1',
                        'pen' => '2',
                        'drinker_number' => '2',
                    ],
                ],
            ],

            // ✅ INVENTORY
            'Inventory' => [
                'feeds' => [
                    [
                        'purchase_date' => '1-20-26',
                        'date_of_monitoring' => '1-27-26',
                        'initial_stock' => '1000',
                        'remaining_stock' => '650',
                    ],
                ],
                'vitamins_e' => [
                    [
                        'purchase_date' => '1-20-26',
                        'date_of_monitoring' => '1-27-26',
                        'type' => 'Liquid',
                        'initial_stock' => '500',
                        'remaining_stock' => '320',
                    ],
                ],
                'vitamins_d3' => [
                    [
                        'purchase_date' => '1-20-26',
                        'date_of_monitoring' => '1-27-26',
                        'type' => 'Liquid',
                        'initial_stock' => '400',
                        'remaining_stock' => '280',
                    ],
                ],
                'vitamins_b_complex' => [
                    [
                        'purchase_date' => '1-20-26',
                        'date_of_monitoring' => '1-27-26',
                        'type' => 'Liquid',
                        'initial_stock' => '450',
                        'remaining_stock' => '300',
                    ],
                ],
            ],

            // ✅ BIOSECURITY
            'Biosecurity' => [
                'cleaning' => [
                    [
                        'house' => '1',
                        'pen' => '4',
                        'activity' => 'Disinfect',
                        'date' => '1-21-26',
                        'time' => '4:43 PM',
                        'disinfectant_used' => 'Vikron',
                        'performed_by' => 'John Doe',
                    ],
                    [
                        'house' => '3',
                        'pen' => '1',
                        'activity' => 'Pest Control',
                        'date' => '1-27-26',
                        'time' => '10:27 AM',
                        'disinfectant_used' => 'Permethrin',
                        'performed_by' => 'Juan Cruz',
                    ],
                ],

                'personnel_biosecurity_logs' => [
                    [
                        'name' => 'John Doe',
                        'role' => 'Flockman',
                        'house' => '1',
                        'date' => '1-21-26',
                        'time' => '4:43 PM',
                        'foot_bath' => 'Yes',
                        'boots_changed' => 'Yes',
                        'protective_clothing' => 'Yes',
                    ],
                ],

                'visitors' => [
                    [
                        'date' => '1-21-26',
                        'time_in' => '11:21 AM',
                        'time_out' => '3:50 PM',
                        'name' => 'John Doe',
                        'purpose' => 'Interview',
                        'foot_bath' => 'Yes',
                        'sanitation' => 'Yes',
                        'ppe' => 'Yes',
                        'monitored_by' => 'Michael Hooper',
                    ],
                ],

                'personnel_entry_logs' => [
                    [
                        'name' => 'John Doe',
                        'role' => 'Flockman',
                        'house' => '1',
                        'date' => '1-25-26',
                        'time' => '4:20 PM',
                    ],
                ],
            ],

            // ✅ WEIGHT SAMPLING
            'Weight Sampling' => [
                [
                    'date' => '1-21-26',
                    'time' => '4:20 PM',
                    'house' => '1',
                    'pen' => '3',
                    'batch' => 'Batch-2026-02',
                    'flocks_with_cases' => '1',
                    'age' => '21 days',
                    'average_weight' => '650 g',
                    'target' => '900 g',
                    'status' => 'Underweight',
                ],
                [
                    'date' => '1-21-26',
                    'time' => '10:02 PM',
                    'house' => '2',
                    'pen' => '4',
                    'batch' => 'Batch-2026-01',
                    'flocks_with_cases' => '2',
                    'age' => '21 days',
                    'average_weight' => '920 g',
                    'target' => '900 g',
                    'status' => 'Normal',
                ],
            ],

            // ✅ TASKS
            'Tasks' => [
                [
                    'name' => 'John Doe',
                    'task_assigned' => 'Feed',
                    'house_number' => '1',
                    'pen_number' => '3',
                    'detailed_task' => 'Feed chickens',
                    'photo' => '/images/sample-proof.jpg',
                    'priority' => 'High',
                    'notes' => 'Urgent',
                    'time_assigned' => '8:00 AM',
                    'finish_by' => '10:00 AM',
                    'time_completed' => '9:30 AM',
                ],
            ],
        ],
    ]);
});