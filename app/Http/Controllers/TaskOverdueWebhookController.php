<?php

namespace App\Http\Controllers;

use App\Services\TaskOverdueAlertService;
use Illuminate\Http\Request;

class TaskOverdueWebhookController extends Controller
{
    public function checkOverdue(Request $request, TaskOverdueAlertService $taskOverdueAlertService)
    {
        $expectedSecret = config('services.task_alerts.webhook_secret');

        if (!$expectedSecret || $request->header('X-Task-Webhook-Secret') !== $expectedSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized webhook request.',
            ], 401);
        }

        $result = $taskOverdueAlertService->checkAndSendAlerts();

        return response()->json([
            'success' => true,
            'message' => 'Overdue tasks checked.',
            'result' => $result,
        ]);
    }
}