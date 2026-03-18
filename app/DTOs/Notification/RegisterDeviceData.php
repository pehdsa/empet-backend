<?php

namespace App\DTOs\Notification;

use App\Enums\DevicePlatform;

final readonly class RegisterDeviceData
{
    public function __construct(
        public string $deviceToken,
        public DevicePlatform $platform,
        public ?string $deviceName = null,
    ) {}
}
