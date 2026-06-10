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
Route::view('/manager/management', 'manager.management')->name('manager.management');
Route::view('/manager/farm-activity-records', 'manager.farm-activity-records')->name('manager.farm-activity-records');
Route::get('/manager/profile', [ProfileController::class, 'managerProfile'])->name('manager.profile');
Route::patch('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');

/*
|--------------------------------------------------------------------------
| ADMIN VIEWS
|--------------------------------------------------------------------------
*/

Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
Route::view('/admin/workers', 'admin.workers')->name('admin.workers');
Route::view('/admin/houses', 'admin.houses')->name('admin.houses');
Route::get('/admin/profile', [ProfileController::class, 'adminProfile'])->name('admin.profile');

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
                'borderColor' => '#17643a',
                'backgroundColor' => 'rgba(23, 100, 58, 0.72)',
            ],
            [
                'label' => 'Ammonia',
                'unit' => 'ppm',
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'values' => [12, 14, 13, 15, 17, 16, 15],
                'borderColor' => '#b7791f',
                'backgroundColor' => 'rgba(183, 121, 31, 0.72)',
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
        'slides' => [
            [
                'label' => 'Temperature',
                'unit' => 'deg',
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'values' => [22, 24, 23, 25, 24, 26, 24],
                'borderColor' => '#17643a',
                'backgroundColor' => 'rgba(23, 100, 58, 0.72)',
            ],
            [
                'label' => 'Ammonia',
                'unit' => 'ppm',
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'values' => [12, 14, 13, 15, 17, 16, 15],
                'borderColor' => '#b7791f',
                'backgroundColor' => 'rgba(183, 121, 31, 0.72)',
            ],
        ],
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
