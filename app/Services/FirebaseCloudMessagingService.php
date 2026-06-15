<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Throwable;

class FirebaseCloudMessagingService
{
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = [],
        string $channelId = 'sensor_alerts'
    ): bool {
        $result = $this->sendToTokenWithResult(
            token: $token,
            title: $title,
            body: $body,
            data: $data,
            channelId: $channelId
        );

        return $result['success'] === true;
    }

    public function sendToTokenWithResult(
        string $token,
        string $title,
        string $body,
        array $data = [],
        string $channelId = 'sensor_alerts'
    ): array {
        try {
            $messaging = (new Factory)
                ->withServiceAccount($this->firebaseCredentials())
                ->createMessaging();

            $androidConfig = AndroidConfig::fromArray([
                'priority' => 'high',
            ]);

            $message = CloudMessage::new()
                ->toToken($token)
                ->withData(array_merge([
                    'title' => $title,
                    'body' => $body,
                    'channel_id' => $channelId,
                ], $data))
                ->withAndroidConfig($androidConfig);

            $messaging->send($message);

            return [
                'success' => true,
                'message' => null,
                'invalid_token' => false,
            ];
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
            $invalidToken = $this->isInvalidTokenError($message);

            \Log::error('Firebase notification failed', [
                'message' => $message,
                'code' => $exception->getCode(),
                'class' => get_class($exception),
                'invalid_token' => $invalidToken,
                'token_start' => substr($token, 0, 25),
            ]);

            return [
                'success' => false,
                'message' => $message,
                'invalid_token' => $invalidToken,
            ];
        }
    }

    private function firebaseCredentials(): array|string
    {
        $credentialsJson = env('FIREBASE_CREDENTIALS_JSON');

        if (!empty($credentialsJson)) {
            $decoded = json_decode($credentialsJson, true);

            if (!is_array($decoded)) {
                throw new \RuntimeException('FIREBASE_CREDENTIALS_JSON is not valid JSON.');
            }

            return $decoded;
        }

        $credentialsPath = env('FIREBASE_CREDENTIALS');

        if (empty($credentialsPath)) {
            throw new \RuntimeException('Firebase credentials are not configured.');
        }

        return base_path($credentialsPath);
    }

    private function isInvalidTokenError(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'requested entity was not found') ||
            str_contains($normalized, 'registration token is not a valid') ||
            str_contains($normalized, 'invalid registration token') ||
            str_contains($normalized, 'invalid-registration-token') ||
            str_contains($normalized, 'registration-token-not-registered') ||
            str_contains($normalized, 'not registered') ||
            str_contains($normalized, 'unregistered') ||
            str_contains($normalized, 'invalid_argument');
    }
}