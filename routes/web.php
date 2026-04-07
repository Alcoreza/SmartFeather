<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Employee;
use App\Models\House;
use App\Models\Pen;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\SensorController;

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

Route::get('/api/manager/tasks', function () {
    $tasks = Task::with(['employee', 'house', 'pen'])
        ->orderBy('timeassigned', 'desc')
        ->get()
        ->map(function (Task $task) {
            $employee = $task->employee;
            $house = $task->house;
            $pen = $task->pen;
            $fullName = trim(sprintf(
                '%s %s %s %s',
                $employee->FirstName ?? '',
                $employee->MiddleName ?? '',
                $employee->LastName ?? '',
                $employee->Suffix ?? '',
            ));

            $formatDate = function ($value) {
                return $value ? Carbon::parse($value)->format('Y-m-d H:i') : '';
            };

            $status = strtolower(trim($task->status ?? 'pending'));
            if ($status === 'for approval' || $status === 'submitted') {
                $status = 'for_approval';
            }

            return [
                'id' => $task->taskid,
                'name' => $fullName ?: 'Unknown',
                'task_assigned' => $task->tasktype,
                'house_number' => $house?->house_number ?? null,
                'pen_number' => $pen?->pen_name ?? 'Unknown Pen',
                'detailed_task' => $task->detailedtask ?? '',
                'priority' => $task->prioritylevel,
                'time_assigned' => $formatDate($task->timeassigned),
                'finish_by' => $formatDate($task->finishby),
                'photo_name' => $task->photourl ? basename($task->photourl) : '',
                'photo_url' => $task->photourl ?? '',
                'notes' => $task->notes ?? '',
                'time_completed' => $formatDate($task->time_completed),
                'status' => $status,
            ];
        });

    return response()->json([
        'pending' => $tasks->where('status', 'pending')->values(),
        'for_approval' => $tasks->where('status', 'for_approval')->values(),
        'completed' => $tasks->where('status', 'completed')->values(),
    ]);
});

Route::post('/api/manager/tasks', function (Request $request) {
    try {
        $validated = $request->validate([
            'user_employeeid' => 'required|integer|exists:user,EmployeeId',
            'tasktype' => 'required|string|max:255',
            'prioritylevel' => 'required|string|max:255',
            'house_houseid' => 'required|integer|exists:house,id',
            'pennumber' => 'required|integer',
            'timeassigned' => 'required|date_format:Y-m-d\TH:i:s',
            'finishby' => 'nullable',
            'detailedtask' => 'nullable|string',
            'status' => 'required|string|in:Pending,For Approval,Completed',
        ]);

        $task = Task::create($validated);

        return response()->json($task, 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Task validation error:', $e->errors());
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        \Log::error('Task creation error:', ['message' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::put('/api/manager/tasks/{taskId}', function ($taskId, Request $request) {
    try {
        $validated = $request->validate([
            'status' => 'required|string|in:Pending,For Approval,Completed',
        ]);

        $task = Task::findOrFail($taskId);
        $task->update($validated);

        return response()->json($task, 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Task update validation error:', $e->errors());
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        \Log::error('Task update error:', ['message' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/api/manager/tasks/form-options', function () {
    $workers = Employee::where('Role', 'Flockman')
        ->get()
        ->map(function (Employee $employee) {
            $fullName = trim(sprintf(
                '%s %s %s %s',
                $employee->FirstName ?? '',
                $employee->MiddleName ?? '',
                $employee->LastName ?? '',
                $employee->Suffix ?? '',
            ));

            return [
                'id' => $employee->EmployeeId,
                'name' => $fullName ?: 'Unknown',
            ];
        });

    $houses = House::all()->map(function (House $house) {
        return [
            'id' => $house->id,
            'number' => $house->house_number,
        ];
    });

    return response()->json([
        'workers' => $workers,
        'houses' => $houses,
        'pens' => [],
        'task_categories' => ['Cleaning', 'Inspection', 'Maintenance', 'Feeding', 'Other'],
        'priority_levels' => ['Low', 'Medium', 'High', 'Urgent'],
    ]);
});

Route::get('/api/manager/tasks/houses/{houseId}/pens', function ($houseId) {
    $pens = Pen::where('house_id', $houseId)
        ->get(['id', 'pen_name'])
        ->map(function (Pen $pen) {
            return [
                'number' => $pen->id,
                'label' => $pen->pen_name,
            ];
        });

    return response()->json([
        'pens' => $pens,
    ]);
});

Route::put('/api/manager/tasks/{taskId}', function ($taskId, Request $request) {
    try {
        $validated = $request->validate([
            'status' => 'required|string|in:Pending,For Approval,Completed',
        ]);

        $task = Task::findOrFail($taskId);
        $task->update($validated);

        return response()->json($task, 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Task update validation error:', $e->errors());
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        \Log::error('Task update error:', ['message' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

/*
|--------------------------------------------------------------------------
| SENSOR PLACEHOLDERS
|--------------------------------------------------------------------------
*/

Route::view('/manager/sensors', 'manager.sensors')->name('manager.sensors');

Route::get('/api/manager/sensors', function () {
    return response()->json([
        'filters' => [
            'names' => ['All Names', 'TMP-1', 'TMP-2', 'AMN-1', 'FDS-1', 'WTR-1'],
            'houses' => ['All Houses', '1', '2', '3'],
            'pens' => ['All Pens', '1', '2', '3', '4'],
        ],
        'sections' => [
            [
                'id' => 'temperature',
                'title' => 'Temperature Sensor',
                'items' => [
                    ['name' => 'TMP-1', 'house_number' => '1', 'pen_number' => '2'],
                    ['name' => 'TMP-2', 'house_number' => '1', 'pen_number' => '2'],
                    ['name' => 'TMP-3', 'house_number' => '1', 'pen_number' => '1'],
                    ['name' => 'TMP-4', 'house_number' => '1', 'pen_number' => '1'],
                ],
            ],
            [
                'id' => 'ammonia',
                'title' => 'Ammonia Sensor',
                'items' => [
                    ['name' => 'AMN-1', 'house_number' => '1', 'pen_number' => '2'],
                    ['name' => 'AMN-2', 'house_number' => '1', 'pen_number' => '2'],
                    ['name' => 'AMN-3', 'house_number' => '1', 'pen_number' => '1'],
                    ['name' => 'AMN-4', 'house_number' => '1', 'pen_number' => '1'],
                ],
            ],
            [
                'id' => 'feeds',
                'title' => 'Feeds Sensor',
                'items' => [
                    ['name' => 'FDS-1', 'house_number' => '1', 'pen_number' => '2'],
                    ['name' => 'FDS-2', 'house_number' => '1', 'pen_number' => '2'],
                    ['name' => 'FDS-3', 'house_number' => '1', 'pen_number' => '1'],
                    ['name' => 'FDS-4', 'house_number' => '1', 'pen_number' => '1'],
                ],
            ],
            [
                'id' => 'water',
                'title' => 'Water Sensor',
                'items' => [
                    ['name' => 'WTR-1', 'house_number' => '1', 'pen_number' => '2'],
                    ['name' => 'WTR-2', 'house_number' => '1', 'pen_number' => '2'],
                    ['name' => 'WTR-3', 'house_number' => '1', 'pen_number' => '1'],
                    ['name' => 'WTR-4', 'house_number' => '1', 'pen_number' => '1'],
                ],
            ],
        ],
    ]);
});

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