<?php

namespace App\DTOs\Pet;

use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use Illuminate\Http\UploadedFile;

final readonly class StorePetData
{
    /**
     * @param  array<int>  $characteristicIds
     * @param  array<UploadedFile>  $photos
     */
    public function __construct(
        public string $name,
        public PetSpecies $species,
        public PetSize $size,
        public PetSex $sex,
        public ?string $breed,
        public ?string $secondaryBreed,
        public ?string $primaryColor,
        public ?string $notes,
        public array $characteristicIds,
        public array $photos,
    ) {}
}
