<?php

namespace App\Listeners;

use App\Events\InvalidPushDeviceDetected;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Log;

class DeactivateInvalidDevices
{
    public function handle(InvalidPushDeviceDetected $event): void
    {
        $count = UserDevice::query()
            ->whereIn('provider_device_id', $event->providerDeviceIds)
            ->update(['is_active' => false]);

        Log::info('DeactivateInvalidDevices: deactivated devices', [
            'provider_device_ids' => $event->providerDeviceIds,
            'deactivated_count' => $count,
        ]);
    }
}
