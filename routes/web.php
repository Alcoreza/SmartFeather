<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;

Route::post('/api/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| WEB VIEWS
|--------------------------------------------------------------------------
*/

Route::view('/', 'auth.login')->name('login');

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
            ['label' => 'Temperature','value' => 24,'unit' => 'deg','min' => 0,'max' => 35,'status' => 'safe'],
            ['label' => 'Ammonia','value' => 7,'unit' => 'ppm','min' => 0,'max' => 30,'status' => 'warning'],
        ],
        'resources' => [
            ['label' => 'Feed','value' => 60,'unit' => '%','type' => 'feed'],
            ['label' => 'Water','value' => 30,'unit' => '%','type' => 'water'],
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
            ['label' => 'Temperature','value' => 24,'unit' => 'deg','min' => 0,'max' => 50,'status' => 'safe'],
            ['label' => 'Ammonia','value' => 15,'unit' => 'ppm','min' => 0,'max' => 50,'status' => 'warning'],
        ],
        'resources' => [
            ['label' => 'Feed','value' => 60,'unit' => '%','type' => 'feed'],
            ['label' => 'Water','value' => 30,'unit' => '%','type' => 'water'],
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
| TASKS API
|--------------------------------------------------------------------------
*/

Route::get('/api/manager/tasks', function () {
    return response()->json([
        'pending' => [],
        'for_approval' => [],
        'completed' => [],
    ]);
});

Route::get('/api/manager/tasks/form-options', function () {
    return response()->json([
        'workers' => [],
        'houses' => [],
        'pens' => [],
        'task_categories' => [],
        'priority_levels' => [],
    ]);
});