<?php

namespace App\Http\Controllers;

use App\Services\SensorAlertService;
use Illuminate\Http\Request;

class SensorAlertWebhookController extends Controller
{
    public function readingCreated(Request $request, SensorAlertService $sensorAlertService)
    {
        return response()->json([
            'success' => false,
            'message' => 'Reading-created webhook is disabled. Use /sensor-alerts/check-latest instead.',
        ], 410);
    }

    public function checkLatest(Request $request, SensorAlertService $sensorAlertService)
    {
        $expectedSecret = env('SENSOR_ALERT_WEBHOOK_SECRET');

        if (!$expectedSecret || $request->header('X-Sensor-Webhook-Secret') !== $expectedSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized alert check request.',
            ], 401);
        }

        $result = $sensorAlertService->checkAndSendAlerts();

        return response()->json([
            'success' => true,
            'message' => 'Latest sensor readings checked.',
            'result' => $result,
        ]);
    }
}