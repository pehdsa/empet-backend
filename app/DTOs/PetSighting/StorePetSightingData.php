<?php

namespace App\DTOs\PetSighting;

use Carbon\CarbonInterface;

final readonly class StorePetSightingData
{
    public function __construct(
        public int $reportId,
        public float $latitude,
        public float $longitude,
        public ?string $addressHint,
        public ?string $description,
        public CarbonInterface $sightedAt,
        public bool $sharePhone = false,
    ) {}
}
