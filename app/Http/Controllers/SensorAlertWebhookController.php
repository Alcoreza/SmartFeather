<?php

namespace App\Http\Controllers;

use App\Services\SensorAlertService;
use Illuminate\Http\Request;

class SensorAlertWebhookController extends Controller
{
    public function readingCreated(Request $request, SensorAlertService $sensorAlertService)
    {
        $expectedSecret = env('SENSOR_ALERT_WEBHOOK_SECRET');

        if (!$expectedSecret || $request->header('X-Sensor-Webhook-Secret') !== $expectedSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized webhook request.',
            ], 401);
        }

        $record = $request->input('record', []);
        $readingId = $record['reading_id'] ?? $request->input('reading_id');

        if (!$readingId) {
            return response()->json([
                'success' => false,
                'message' => 'Missing reading_id from webhook payload.',
            ], 422);
        }

        $result = $sensorAlertService->processReadingId((int) $readingId);

        return response()->json([
            'success' => true,
            'message' => 'Sensor reading processed.',
            'result' => $result,
        ]);
    }
}