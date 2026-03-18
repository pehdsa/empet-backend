<?php

namespace App\Actions\Notification;

use App\Contracts\PushNotificationService;
use App\DTOs\Notification\RegisterDeviceData;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Log;

class RegisterDevice
{
    public function __construct(
        private readonly PushNotificationService $pushService,
    ) {}

    /**
     * Register a device for push notifications.
     */
    public function handle(RegisterDeviceData $data, User $user): UserDevice
    {
        // Deactivate token if it belongs to another user
        UserDevice::query()
            ->where('device_token', $data->deviceToken)
            ->where('user_id', '!=', $user->id)
            ->update([
                'is_active' => false,
                'provider_device_id' => null,
            ]);

        $device = UserDevice::updateOrCreate(
            ['device_token' => $data->deviceToken],
            [
                'user_id' => $user->id,
                'platform' => $data->platform,
                'device_name' => $data->deviceName,
                'is_active' => true,
                'last_active_at' => now(),
            ],
        );

        try {
            $providerDeviceId = $this->pushService->registerDevice(
                externalUserId: (string) $user->id,
                deviceToken: $data->deviceToken,
                platform: $data->platform->value,
            );

            if ($providerDeviceId) {
                $device->update(['provider_device_id' => $providerDeviceId]);
            }
        } catch (\Throwable $e) {
            Log::error('RegisterDevice: failed to register with push provider', [
                'user_id' => $user->id,
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $device;
    }
}
