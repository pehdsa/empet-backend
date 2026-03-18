<?php

namespace App\DTOs\Notification;

final readonly class UpdateNotificationSettingData
{
    public function __construct(
        public ?bool $notifyLostNearby = null,
        public ?bool $notifyMatches = null,
        public ?bool $notifySightings = null,
        public ?int $nearbyRadiusKm = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $clearLocation = false,
    ) {}
}
