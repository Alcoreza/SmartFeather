<?php

namespace App\Http\Controllers;

use App\Services\FirebaseCloudMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationQueueController extends Controller
{
    private int $maxAttempts = 3;
    private int $batchSize = 10;

    public function processOne(Request $request, FirebaseCloudMessagingService $firebase)
    {
        $expectedSecret = env('NOTIFICATION_QUEUE_SECRET');

        if (!$expectedSecret || $request->header('X-Notification-Queue-Secret') !== $expectedSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized notification queue request.',
            ], 401);
        }

        $jobs = DB::transaction(function () {
            $jobs = DB::table('notification_queue')
                ->where('status', 'pending')
                ->where('available_at', '<=', now())
                ->orderBy('id')
                ->limit($this->batchSize)
                ->lock('FOR UPDATE SKIP LOCKED')
                ->get();

            if ($jobs->isEmpty()) {
                return collect();
            }

            DB::table('notification_queue')
                ->whereIn('id', $jobs->pluck('id')->all())
                ->update([
                    'status' => 'processing',
                    'attempts' => DB::raw('attempts + 1'),
                    'locked_at' => now(),
                    'updated_at' => now(),
                ]);

            return $jobs;
        });

        if ($jobs->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No pending notification.',
                'processed' => false,
                'processed_count' => 0,
                'sent_count' => 0,
                'failed_count' => 0,
                'retry_count' => 0,
                'invalid_token_count' => 0,
            ]);
        }

        $sentCount = 0;
        $failedCount = 0;
        $retryCount = 0;
        $invalidTokenCount = 0;
        $results = [];

        foreach ($jobs as $job) {
            $result = $this->processJob($job, $firebase);

            $sentCount += $result['sent'] ? 1 : 0;
            $failedCount += $result['failed'] ? 1 : 0;
            $retryCount += $result['retry'] ? 1 : 0;
            $invalidTokenCount += $result['invalid_token'] ? 1 : 0;

            $results[] = [
                'job_id' => $job->id,
                'status' => $result['status'],
                'message' => $result['message'],
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification queue batch processed.',
            'processed' => true,
            'processed_count' => count($results),
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'retry_count' => $retryCount,
            'invalid_token_count' => $invalidTokenCount,
            'results' => $results,
        ]);
    }

    private function processJob($job, FirebaseCloudMessagingService $firebase): array
    {
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

            return [
                'status' => 'sent',
                'message' => 'Notification sent.',
                'sent' => true,
                'failed' => false,
                'retry' => false,
                'invalid_token' => false,
            ];
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

            return [
                'status' => 'failed',
                'message' => 'Invalid FCM token deactivated.',
                'sent' => false,
                'failed' => true,
                'retry' => false,
                'invalid_token' => true,
            ];
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

        return [
            'status' => $status,
            'message' => $status === 'pending'
                ? 'Notification send failed and was queued for retry.'
                : 'Notification send failed permanently.',
            'sent' => false,
            'failed' => $status === 'failed',
            'retry' => $status === 'pending',
            'invalid_token' => false,
        ];
    }
}