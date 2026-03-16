<?php

namespace App\DTOs\PetReport;

use App\Models\PetReport;
use Carbon\CarbonInterface;

final readonly class UpdatePetReportData
{
    public function __construct(
        public PetReport $report,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $addressHint,
        public ?string $description,
        public ?CarbonInterface $lostAt,
    ) {}
}
