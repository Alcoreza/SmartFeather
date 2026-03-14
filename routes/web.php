<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login')->name('login');
Route::view('/manager/dashboard', 'manager.dashboard')->name('manager.dashboard');
Route::view('/manager/workers', 'manager.workers')->name('manager.workers');

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

Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');

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

Route::view('/admin/workers', 'admin.workers')->name('admin.workers');

Route::get('/api/admin/workers', function () {
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
