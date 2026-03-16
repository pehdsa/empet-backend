<?php

namespace App\DTOs\PetReport;

use Carbon\CarbonInterface;

final readonly class StorePetReportData
{
    public function __construct(
        public int $petId,
        public float $latitude,
        public float $longitude,
        public ?string $addressHint,
        public ?string $description,
        public CarbonInterface $lostAt,
    ) {}
}
