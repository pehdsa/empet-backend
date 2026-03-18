<?php

namespace App\Channels;

use App\Contracts\PushNotificationService;
use App\DTOs\Notification\PushNotificationPayload;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PushChannel
{
    public function __construct(
        private readonly PushNotificationService $pushService,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        /** @var PushNotificationPayload $payload */
        $payload = $notification->toPush($notifiable);

        $providerDeviceIds = $notifiable->devices()
            ->where('is_active', true)
            ->whereNotNull('provider_device_id')
            ->pluck('provider_device_id')
            ->toArray();

        if (empty($providerDeviceIds)) {
            return;
        }

        try {
            $this->pushService->sendToDevices($providerDeviceIds, $payload);
        } catch (\Throwable $e) {
            Log::error('PushChannel: failed to send push notification', [
                'notifiable_id' => $notifiable->getKey(),
                'notification_type' => get_class($notification),
                'provider_device_ids' => $providerDeviceIds,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
