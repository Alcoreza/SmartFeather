<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\MobileTaskController;
use App\Http\Controllers\MobilePopulationController;

Route::post('/mobile/login', [MobileAuthController::class, 'login']);
Route::post('/mobile/tasks', [MobileTaskController::class, 'getFlockmanTasks']);
Route::post('/mobile/tasks/submit', [MobileTaskController::class, 'submitTaskForApproval']);
Route::get('/mobile/houses', [MobilePopulationController::class, 'getHouses']);
Route::post('/mobile/population', [MobilePopulationController::class, 'submit']);