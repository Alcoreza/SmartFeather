<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\MobileTaskController;
use App\Http\Controllers\MobilePopulationController;
use App\Http\Controllers\MobileFeedsRefillController;
use App\Http\Controllers\MobileVitaminsRefillController;
use App\Http\Controllers\MobileProfileController;
use App\Http\Controllers\MobileDisinfectionController;
use App\Http\Controllers\MobileVisitorController;
use App\Http\Controllers\MobileWeightSamplingController;
use App\Http\Controllers\MobileNewBatchController;
use App\Http\Controllers\MobileDashboardController;
use App\Http\Controllers\MobilePersonnelLogsController;


Route::post('/mobile/login', [MobileAuthController::class, 'login']);

Route::post('/mobile/tasks', [MobileTaskController::class, 'getFlockmanTasks']);
Route::post('/mobile/tasks/submit', [MobileTaskController::class, 'submitTaskForApproval']);

Route::get('/mobile/houses', [MobilePopulationController::class, 'getHouses']);

Route::post('/mobile/population', [MobilePopulationController::class, 'submit']);
Route::get('/mobile/population/context', [MobilePopulationController::class, 'getContext']);


Route::get('/mobile/feed-refill/houses', [MobileFeedsRefillController::class, 'getHouses']);
Route::get('/mobile/feed-refill/houses/{houseId}/pens', [MobileFeedsRefillController::class, 'getPensByHouse']);
Route::get('/mobile/feed-refill/feed-options', [MobileFeedsRefillController::class, 'getFeedInventoryOptions']);
Route::post('/mobile/feed-refill', [MobileFeedsRefillController::class, 'submit']);
Route::get('/mobile/feed-refill/context', [MobileFeedsRefillController::class, 'getContext']);

Route::get('/mobile/vitamin-refill/houses', [MobileVitaminsRefillController::class, 'getHouses']);
Route::get('/mobile/vitamin-refill/houses/{houseId}/pens', [MobileVitaminsRefillController::class, 'getPensByHouse']);
Route::get('/mobile/vitamin-refill/options', [MobileVitaminsRefillController::class, 'getVitaminInventoryOptions']);
Route::post('/mobile/vitamin-refill', [MobileVitaminsRefillController::class, 'submit']);
Route::get('/mobile/vitamin-refill/context', [MobileVitaminsRefillController::class, 'getContext']);


Route::get('/mobile/profile/{employeeId}', [MobileProfileController::class, 'show']);
Route::put('/mobile/profile/{employeeId}', [MobileProfileController::class, 'update']);

Route::get('/mobile/disinfection/context', [MobileDisinfectionController::class, 'getContext']);
Route::get('/mobile/disinfection/houses', [MobileDisinfectionController::class, 'getHouses']);
Route::get('/mobile/disinfection/houses/{houseId}/pens', [MobileDisinfectionController::class, 'getPensByHouse']);
Route::post('/mobile/disinfection', [MobileDisinfectionController::class, 'submit']);

Route::post('/mobile/visitor', [MobileVisitorController::class, 'submit']);
Route::post('/mobile/visitor/photo-upload-url', [MobileVisitorController::class, 'createVisitorPhotoUploadUrl']);

Route::get('/mobile/weight-sampling/context', [MobileWeightSamplingController::class, 'getContext']);
Route::get('/mobile/weight-sampling/houses', [MobileWeightSamplingController::class, 'getHouses']);
Route::get('/mobile/weight-sampling/houses/{houseId}/pens', [MobileWeightSamplingController::class, 'getPensByHouse']);
Route::post('/mobile/weight-sampling', [MobileWeightSamplingController::class, 'submit']);

Route::get('/mobile/new-batch/context', [MobileNewBatchController::class, 'getContext']);
Route::get('/mobile/new-batch/houses', [MobileNewBatchController::class, 'getHouses']);
Route::get('/mobile/new-batch/houses/{houseId}/pens', [MobileNewBatchController::class, 'getPensByHouse']);
Route::post('/mobile/new-batch', [MobileNewBatchController::class, 'submit']);

Route::post('/mobile/tasks/photo-upload-url', [MobileTaskController::class, 'createTaskPhotoUploadUrl']);

Route::get('/mobile/dashboard', [MobileDashboardController::class, 'show']);

Route::get('/mobile/personnel-logs/context', [MobilePersonnelLogsController::class, 'context']);
Route::post('/mobile/personnel-logs', [MobilePersonnelLogsController::class, 'submit']);


