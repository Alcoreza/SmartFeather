<?php

namespace App\Http\Controllers;

use App\Services\FirebaseCloudMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationQueueController extends Controller
{
    private int $maxAttempts = 3;

    public function processOne(Request $request, FirebaseCloudMessagingService $firebase)
    {
        $expectedSecret = env('NOTIFICATION_QUEUE_SECRET');

        if (!$expectedSecret || $request->header('X-Notification-Queue-Secret') !== $expectedSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized notification queue request.',
            ], 401);
        }

        $job = DB::transaction(function () {
            $job = DB::table('notification_queue')
                ->where('status', 'pending')
                ->where('available_at', '<=', now())
                ->orderBy('id')
                ->lock('FOR UPDATE SKIP LOCKED')
                ->first();

            if (!$job) {
                return null;
            }

            DB::table('notification_queue')
                ->where('id', $job->id)
                ->update([
                    'status' => 'processing',
                    'attempts' => DB::raw('attempts + 1'),
                    'locked_at' => now(),
                    'updated_at' => now(),
                ]);

            return $job;
        });

        if (!$job) {
            return response()->json([
                'success' => true,
                'message' => 'No pending notification.',
                'processed' => false,
            ]);
        }

        $data = [];

        if (!empty($job->data)) {
            $decoded = json_decode((string) $job->data, true);
            $data = is_array($decoded) ? $decoded : [];
        }

        $result = $firebase->sendToTokenWithResult(
            token: $job->fcm_token,
            title: $job->title,
            body: $job->body,
            data: $data,
            channelId: $job->channel_id ?: 'sensor_alerts'
        );

        if ($result['success'] === true) {
            DB::table('notification_queue')
                ->where('id', $job->id)
                ->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'locked_at' => null,
                    'last_error' => null,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification sent.',
                'processed' => true,
                'job_id' => $job->id,
            ]);
        }

        $errorMessage = $result['message'] ?? 'Firebase send failed.';
        $isInvalidToken = $result['invalid_token'] === true;

        if ($isInvalidToken) {
            DB::table('mobile_device_tokens')
                ->where('fcm_token', $job->fcm_token)
                ->update([
                    'is_active' => DB::raw('false'),
                    'updated_at' => now(),
                ]);

            DB::table('notification_queue')
                ->where('id', $job->id)
                ->update([
                    'status' => 'failed',
                    'locked_at' => null,
                    'last_error' => 'Invalid FCM token: ' . $errorMessage,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Invalid FCM token deactivated.',
                'processed' => true,
                'job_id' => $job->id,
                'status' => 'failed',
            ]);
        }

        $attempts = ((int) $job->attempts) + 1;
        $status = $attempts >= $this->maxAttempts ? 'failed' : 'pending';

        DB::table('notification_queue')
            ->where('id', $job->id)
            ->update([
                'status' => $status,
                'available_at' => $status === 'pending' ? now()->addMinutes(2) : $job->available_at,
                'locked_at' => null,
                'last_error' => $errorMessage,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification send failed and was handled.',
            'processed' => true,
            'job_id' => $job->id,
            'status' => $status,
        ]);
    }
}