<?php

namespace App\Services\Push;

use App\Contracts\PushNotificationService;
use App\DTOs\Notification\PushNotificationPayload;
use App\Events\InvalidPushDeviceDetected;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalPushService implements PushNotificationService
{
    private const BASE_URL = 'https://api.onesignal.com/api/v1';

    /**
     * @param  array<string>  $providerDeviceIds
     */
    public function sendToDevices(array $providerDeviceIds, PushNotificationPayload $payload): bool
    {
        $body = [
            'app_id' => config('services.onesignal.app_id'),
            'include_subscription_ids' => $providerDeviceIds,
            'headings' => ['en' => $payload->title],
            'contents' => ['en' => $payload->body],
            'data' => $payload->data,
        ];

        if ($payload->imageUrl) {
            $body['big_picture'] = $payload->imageUrl;
            $body['ios_attachments'] = ['image' => $payload->imageUrl];
        }

        if ($payload->category) {
            $body['android_group'] = $payload->category;
            $body['thread_id'] = $payload->category;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Key '.config('services.onesignal.rest_api_key'),
        ])->post(self::BASE_URL.'/notifications', $body);

        if ($response->failed()) {
            Log::error('OneSignal: failed to send notification', [
                'status' => $response->status(),
                'body' => $response->json(),
                'provider_device_ids' => $providerDeviceIds,
            ]);

            return false;
        }

        $responseData = $response->json();
        $invalidIds = $responseData['errors']['invalid_player_ids'] ?? [];

        if (! empty($invalidIds)) {
            Log::warning('OneSignal: invalid player IDs detected', [
                'invalid_ids' => $invalidIds,
            ]);

            event(new InvalidPushDeviceDetected($invalidIds));
        }

        return true;
    }

    public function registerDevice(string $externalUserId, string $deviceToken, string $platform): ?string
    {
        $deviceType = match (strtoupper($platform)) {
            'IOS' => 0,
            'ANDROID' => 1,
            default => 1,
        };

        $response = Http::withHeaders([
            'Authorization' => 'Key '.config('services.onesignal.rest_api_key'),
        ])->post(self::BASE_URL.'/players', [
            'app_id' => config('services.onesignal.app_id'),
            'identifier' => $deviceToken,
            'device_type' => $deviceType,
            'external_user_id' => $externalUserId,
        ]);

        if ($response->failed()) {
            Log::error('OneSignal: failed to register device', [
                'status' => $response->status(),
                'body' => $response->json(),
                'external_user_id' => $externalUserId,
            ]);

            return null;
        }

        return $response->json('id');
    }

    public function removeDevice(string $providerDeviceId): bool
    {
        $response = Http::withHeaders([
            'Authorization' => 'Key '.config('services.onesignal.rest_api_key'),
        ])->delete(self::BASE_URL.'/players/'.$providerDeviceId, [
            'app_id' => config('services.onesignal.app_id'),
        ]);

        if ($response->failed()) {
            Log::error('OneSignal: failed to remove device', [
                'status' => $response->status(),
                'body' => $response->json(),
                'provider_device_id' => $providerDeviceId,
            ]);

            return false;
        }

        return true;
    }
}
