<?php

namespace App\Actions\Notification;

use App\Contracts\PushNotificationService;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Log;

class RemoveDevice
{
    public function __construct(
        private readonly PushNotificationService $pushService,
    ) {}

    /**
     * Deactivate a device and remove it from the push provider.
     */
    public function handle(UserDevice $device): UserDevice
    {
        $device->update(['is_active' => false]);

        if ($device->provider_device_id) {
            try {
                $this->pushService->removeDevice($device->provider_device_id);
            } catch (\Throwable $e) {
                Log::error('RemoveDevice: failed to remove from push provider', [
                    'device_id' => $device->id,
                    'provider_device_id' => $device->provider_device_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $device;
    }
}
