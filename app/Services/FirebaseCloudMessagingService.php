<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FirebaseCloudMessagingService
{
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): bool {
        try {
            $credentials = base_path(env('FIREBASE_CREDENTIALS'));

            $messaging = (new Factory)
                ->withServiceAccount($credentials)
                ->createMessaging();

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData(array_merge([
                    'title' => $title,
                    'body' => $body,
                ], $data));

            $messaging->send($message);

            return true;
        } catch (Throwable $exception) {
            \Log::error('Firebase notification failed', [
                'message' => $exception->getMessage(),
                'token_start' => substr($token, 0, 25),
            ]);

            return false;
        }
    }
}