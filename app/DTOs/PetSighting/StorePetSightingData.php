<?php

namespace App\DTOs\PetSighting;

use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;

final readonly class StorePetSightingData
{
    /**
     * @param  array<int, UploadedFile>  $photos
     * @param  array<int, int>  $characteristicIds
     */
    public function __construct(
        public string $title,
        public float $latitude,
        public float $longitude,
        public CarbonInterface $sightedAt,
        public string $species,
        public ?string $size,
        public ?string $sex,
        public ?string $color,
        public ?int $breedId,
        public ?string $addressHint,
        public ?string $description,
        public bool $sharePhone = false,
        public array $photos = [],
        public array $characteristicIds = [],
    ) {}
}
