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
use App\Http\Controllers\MobilePenCleaningController;
use App\Http\Controllers\MobileSensorInspectionController;
use App\Http\Controllers\MobileDeviceTokenController;
use App\Http\Controllers\SensorAlertWebhookController;
use App\Http\Controllers\TaskOverdueWebhookController;
use App\Http\Controllers\NotificationQueueController;

Route::post('/mobile/login', [MobileAuthController::class, 'login']);

Route::middleware(['throttle:170,1', 'mobile.auth'])->group(function () {
    Route::post('/mobile/tasks', [MobileTaskController::class, 'getFlockmanTasks']);
    Route::post('/mobile/tasks/submit', [MobileTaskController::class, 'submitTaskForApproval']);
    Route::post('/mobile/tasks/photo-upload-url', [MobileTaskController::class, 'createTaskPhotoUploadUrl']);
    Route::post('/mobile/tasks/access-check', [MobileTaskController::class, 'checkTaskAccess']);
    Route::post('/mobile/tasks/submitted-detail', [MobileTaskController::class, 'getSubmittedTaskDetail']);

    Route::get('/mobile/houses', [MobilePopulationController::class, 'getHouses']);
    Route::post('/mobile/population', [MobilePopulationController::class, 'submit']);
    Route::get('/mobile/population/context', [MobilePopulationController::class, 'getContext']);

    Route::get('/mobile/feed-refill/houses', [MobileFeedsRefillController::class, 'getHouses']);
    Route::get('/mobile/feed-refill/houses/{houseId}/pens', [MobileFeedsRefillController::class, 'getPensByHouse']);
    Route::get('/mobile/feed-refill/feed-options', [MobileFeedsRefillController::class, 'getFeedInventoryOptions']);
    Route::post('/mobile/feed-refill', [MobileFeedsRefillController::class, 'submit']);
    Route::get('/mobile/feed-refill/context', [MobileFeedsRefillController::class, 'getContext']);
    Route::get('/mobile/feed-refill/houses/{houseId}/pens/{penId}/feeders', [MobileFeedsRefillController::class, 'getFeederOptions']);

    Route::get('/mobile/vitamin-refill/houses', [MobileVitaminsRefillController::class, 'getHouses']);
    Route::get('/mobile/vitamin-refill/houses/{houseId}/pens', [MobileVitaminsRefillController::class, 'getPensByHouse']);
    Route::get('/mobile/vitamin-refill/options', [MobileVitaminsRefillController::class, 'getVitaminInventoryOptions']);
    Route::post('/mobile/vitamin-refill', [MobileVitaminsRefillController::class, 'submit']);
    Route::get('/mobile/vitamin-refill/context', [MobileVitaminsRefillController::class, 'getContext']);

    Route::get('/mobile/profile', [MobileProfileController::class, 'show']);
    Route::put('/mobile/profile', [MobileProfileController::class, 'update']);

    Route::get('/mobile/disinfection/context', [MobileDisinfectionController::class, 'getContext']);
    Route::get('/mobile/disinfection/houses', [MobileDisinfectionController::class, 'getHouses']);
    Route::get('/mobile/disinfection/houses/{houseId}/pens', [MobileDisinfectionController::class, 'getPensByHouse']);
    Route::post('/mobile/disinfection', [MobileDisinfectionController::class, 'submit']);

    Route::post('/mobile/visitor/time-in', [MobileVisitorController::class, 'timeIn']);
    Route::get('/mobile/visitor/open', [MobileVisitorController::class, 'getOpenVisitors']);
    Route::post('/mobile/visitor/time-out', [MobileVisitorController::class, 'timeOut']);
    Route::post('/mobile/visitor/photo-upload-url', [MobileVisitorController::class, 'createVisitorPhotoUploadUrl']);

    Route::get('/mobile/weight-sampling/context', [MobileWeightSamplingController::class, 'getContext']);
    Route::get('/mobile/weight-sampling/houses', [MobileWeightSamplingController::class, 'getHouses']);
    Route::get('/mobile/weight-sampling/houses/{houseId}/pens', [MobileWeightSamplingController::class, 'getPensByHouse']);
    Route::post('/mobile/weight-sampling', [MobileWeightSamplingController::class, 'submit']);

    Route::get('/mobile/new-batch/context', [MobileNewBatchController::class, 'getContext']);
    Route::get('/mobile/new-batch/houses', [MobileNewBatchController::class, 'getHouses']);
    Route::get('/mobile/new-batch/houses/{houseId}/pens', [MobileNewBatchController::class, 'getPensByHouse']);
    Route::post('/mobile/new-batch', [MobileNewBatchController::class, 'submit']);

    Route::get('/mobile/dashboard', [MobileDashboardController::class, 'show']);

    Route::get('/mobile/personnel-logs/context', [MobilePersonnelLogsController::class, 'context']);
    Route::post('/mobile/personnel-logs', [MobilePersonnelLogsController::class, 'submit']);

    Route::get('/mobile/pen-cleaning/context', [MobilePenCleaningController::class, 'getContext']);
    Route::post('/mobile/pen-cleaning', [MobilePenCleaningController::class, 'submit']);

    Route::post('/mobile/sensor-inspection', [MobileSensorInspectionController::class, 'submit']);

    Route::post('/mobile/device-token', [MobileDeviceTokenController::class, 'store']);
});

Route::post('/sensor-alerts/check-latest', [SensorAlertWebhookController::class, 'checkLatest'])
    ->middleware('throttle:30,1');

Route::post('/notification-queue/process-one', [NotificationQueueController::class, 'processOne'])
    ->middleware('throttle:30,1');

Route::post('/task-alerts/check-overdue', [TaskOverdueWebhookController::class, 'checkOverdue'])
    ->middleware('throttle:30,1');