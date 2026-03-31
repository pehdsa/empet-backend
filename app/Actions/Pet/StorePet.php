<?php

namespace App\Actions\Pet;

use App\DTOs\Pet\StorePetData;
use App\Models\Pet;
use App\Models\User;
use App\Services\Image\ImageConverter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StorePet
{
    public function __construct(
        private readonly ImageConverter $imageConverter,
    ) {}

    /**
     * Create a new pet for the given user.
     */
    public function handle(StorePetData $data, User $user): Pet
    {
        return DB::transaction(function () use ($data, $user): Pet {
            $pet = $user->pets()->create([
                'name' => $data->name,
                'species' => $data->species,
                'size' => $data->size,
                'sex' => $data->sex,
                'breed_id' => $data->breedId,
                'secondary_breed_id' => $data->secondaryBreedId,
                'breed_description' => $data->breedDescription,
                'primary_color' => $data->primaryColor,
                'notes' => $data->notes,
            ]);

            foreach ($data->photos as $position => $photo) {
                $converted = $this->imageConverter->toJpg($photo);

                $path = $converted->storeAs(
                    'pets/photos',
                    Str::ulid().'.jpg',
                    's3',
                );

                $pet->photos()->create([
                    'path' => $path,
                    'position' => $position,
                ]);
            }

            if (! empty($data->characteristicIds)) {
                $pet->characteristics()->sync($data->characteristicIds);
            }

            return $pet->load(['photos', 'characteristics', 'breed', 'secondaryBreed', 'activeReport']);
        });
    }
}
