<?php

namespace App\DTOs\Pet;

use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use App\Models\Pet;
use Illuminate\Http\UploadedFile;

final readonly class UpdatePetData
{
    /**
     * @param  ?array<int>  $characteristicIds  null = keep current, [] = clear all, [1,2] = sync
     * @param  ?array<UploadedFile>  $newPhotos  null = no new photos
     * @param  ?array<int>  $deletePhotoIds  null = don't remove any
     */
    public function __construct(
        public Pet $pet,
        public string $name,
        public PetSpecies $species,
        public PetSize $size,
        public PetSex $sex,
        public ?int $breedId,
        public ?int $secondaryBreedId,
        public ?string $breedDescription,
        public ?string $primaryColor,
        public ?string $notes,
        public ?array $characteristicIds,
        public ?array $newPhotos,
        public ?array $deletePhotoIds,
    ) {}
}
