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

Route::view('/manager/dashboard', 'manager.dashboard')->name('manager.dashboard');
Route::view('/manager/workers', 'manager.workers')->name('manager.workers');
Route::view('/manager/houses', 'manager.houses')->name('manager.houses');
Route::view('/manager/houses/records', 'manager.record-house')
    ->name('manager.houses.record-house');

Route::get('/manager/inventory', [InventoryController::class, 'managerIndex'])->name('manager.inventory');

Route::view('/manager/inventory/records', 'manager.record-inventory')
    ->name('manager.inventory.records');
Route::view('/manager/inventory/records/feed', 'manager.record-inventory-feed')
    ->name('manager.inventory.records.feed');
Route::view('/manager/inventory/records/vitamins', 'manager.record-inventory')
    ->name('manager.inventory.records.vitamins');

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
            [
                'label' => 'Temperature',
                'value' => 24,
                'unit' => 'deg',
                'min' => 0,
                'max' => 35,
                'status' => 'safe',
            ],
            [
                'label' => 'Ammonia',
                'value' => 7,
                'unit' => 'ppm',
                'min' => 0,
                'max' => 30,
                'status' => 'warning',
            ],
        ],
        'resources' => [
            [
                'label' => 'Feed',
                'value' => 60,
                'unit' => '%',
                'type' => 'feed',
            ],
            [
                'label' => 'Water',
                'value' => 30,
                'unit' => '%',
                'type' => 'water',
            ],
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
            'suffix' => '',
            'name' => 'Juan Dela Cruz',
            'role' => 'Manager',
            'phone' => '09218729021',
            'birthday' => '07/24/1993',
            'gender' => 'Male',
            'address' => 'Sitio Burol Lucban, Quezon',
        ],
        [
            'id' => 2,
            'first_name' => 'John',
            'middle_name' => '',
            'last_name' => 'Doe',
            'suffix' => '',
            'name' => 'John Doe',
            'role' => 'Admin',
            'phone' => '09171234567',
            'birthday' => '03/14/1998',
            'gender' => 'Male',
            'address' => 'Lucena City, Quezon',
        ],
        [
            'id' => 3,
            'first_name' => 'Bob',
            'middle_name' => '',
            'last_name' => 'Dela Cruz',
            'suffix' => '',
            'name' => 'Bob Dela Cruz',
            'role' => 'Flockman',
            'phone' => '09991234567',
            'birthday' => '11/21/1995',
            'gender' => 'Male',
            'address' => 'Sariaya, Quezon',
        ],
    ]);
});


/*
|--------------------------------------------------------------------------
| ADMIN API (REAL CRUD)
|--------------------------------------------------------------------------
*/

Route::get('/api/admin/dashboard/realtime', function () {
    return response()->json([
        'environment' => [
            [
                'label' => 'Temperature',
                'value' => 24,
                'unit' => 'deg',
                'min' => 0,
                'max' => 50,
                'status' => 'safe',
            ],
            [
                'label' => 'Ammonia',
                'value' => 15,
                'unit' => 'ppm',
                'min' => 0,
                'max' => 50,
                'status' => 'warning',
            ],
        ],
        'resources' => [
            [
                'label' => 'Feed',
                'value' => 60,
                'unit' => '%',
                'type' => 'feed',
            ],
            [
                'label' => 'Water',
                'value' => 30,
                'unit' => '%',
                'type' => 'water',
            ],
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
| ✅ NEW: MANAGER INVENTORY CRUD (ADDED)
|--------------------------------------------------------------------------
*/

Route::prefix('api/manager/inventory')->group(function () {
    Route::post('/', [InventoryController::class, 'store']);
    Route::put('/{id}', [InventoryController::class, 'update']);
    Route::delete('/{id}', [InventoryController::class, 'destroy']);
});


Route::view('/manager/tasks', 'manager.tasks')->name('manager.tasks');

Route::get('/api/manager/tasks', function () {
    return response()->json([
        'pending' => [
            [
                'name' => 'Juan Dela Cruz',
                'task_assigned' => 'Inspection',
                'house_number' => '2',
                'pen_number' => '2',
                'detailed_task' => 'Inspect the arrangement of the lights and current roofing condition',
                'priority' => 'High',
                'time_assigned' => '1-27-2026 9:37 PM',
                'finish_by' => '1-28-2026 10:00 AM',
            ],
        ],
        'for_approval' => [
            [
                'id' => 1,
                'name' => 'Juan Dela Cruz',
                'task_assigned' => 'Inspection',
                'house_number' => '2',
                'pen_number' => '2',
                'detailed_task' => 'Inspect the arrangement of the lights and current roofing condition',
                'photo_name' => 'proof.jpg',
                'photo_url' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?auto=format&fit=crop&w=1200&q=80',
                'priority' => 'High',
                'time_assigned' => '1-27-2026 9:37 PM',
                'finish_by' => '1-28-2026 10:00 AM',
            ],
        ],
        'completed' => [
            [
                'id' => 2,
                'name' => 'Juan Dela Cruz',
                'task_assigned' => 'Inspection',
                'house_number' => '2',
                'pen_number' => '2',
                'detailed_task' => 'Inspect the arrangement of the lights and current roofing condition',
                'photo_name' => 'proof.jpg',
                'photo_url' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?auto=format&fit=crop&w=1200&q=80',
                'priority' => 'High',
                'notes' => 'The lights and roofing condition is still intact',
                'time_assigned' => '1-27-2026 9:37 PM',
                'finish_by' => '1-28-2026 10:00 AM',
                'time_completed' => '1-27-2026 5:25 PM',
            ],
        ],
    ]);
});

Route::get('/api/manager/tasks/form-options', function () {
    return response()->json([
        'workers' => [
            ['id' => 1, 'name' => 'Juan Dela Cruz'],
            ['id' => 2, 'name' => 'John Doe'],
            ['id' => 3, 'name' => 'Bob Dela Cruz'],
        ],
        'houses' => [
            ['id' => 1, 'number' => '1'],
            ['id' => 2, 'number' => '2'],
            ['id' => 3, 'number' => '3'],
        ],
        'pens' => [
            ['id' => 1, 'number' => '1'],
            ['id' => 2, 'number' => '2'],
            ['id' => 3, 'number' => '3'],
            ['id' => 4, 'number' => '4'],
        ],
        'task_categories' => [
            'Inspection',
            'Maintenance',
            'Feeds Refill',
            'Vitamins Refill',
            'Flock Population',
            'Weight Sampling',
            'New Bird Batch',
            'Disinfection',
            'Personnel Logs',
            'Visitor',
        ],
        'priority_levels' => [
            'Low',
            'Medium',
            'High',
        ],
    ]);
});
