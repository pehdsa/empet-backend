<?php

namespace App\Services\Push;

use App\Contracts\PushNotificationService;
use App\DTOs\Notification\PushNotificationPayload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogPushService implements PushNotificationService
{
    /**
     * {@inheritDoc}
     */
    public function sendToDevices(array $providerDeviceIds, PushNotificationPayload $payload): bool
    {
        Log::info('LogPushService: sendToDevices', [
            'provider_device_ids' => $providerDeviceIds,
            'title' => $payload->title,
            'body' => $payload->body,
            'data' => $payload->data,
            'category' => $payload->category,
        ]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function registerDevice(string $externalUserId, string $deviceToken, string $platform): ?string
    {
        $fakeProviderId = 'fake-'.Str::uuid()->toString();

        Log::info('LogPushService: registerDevice', [
            'external_user_id' => $externalUserId,
            'device_token' => $deviceToken,
            'platform' => $platform,
            'fake_provider_id' => $fakeProviderId,
        ]);

        return $fakeProviderId;
    }

    /**
     * {@inheritDoc}
     */
    public function removeDevice(string $providerDeviceId): bool
    {
        Log::info('LogPushService: removeDevice', [
            'provider_device_id' => $providerDeviceId,
        ]);

        return true;
    }
}
