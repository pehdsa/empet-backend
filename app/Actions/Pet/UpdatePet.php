<?php

namespace App\Actions\Pet;

use App\DTOs\Pet\UpdatePetData;
use App\Models\Pet;
use App\Services\Image\ImageConverter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdatePet
{
    public function __construct(
        private readonly ImageConverter $imageConverter,
    ) {}

    /**
     * Update an existing pet.
     */
    public function handle(UpdatePetData $data): Pet
    {
        return DB::transaction(function () use ($data): Pet {
            $pet = $data->pet;

            $pet->update([
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

            if ($data->deletePhotoIds !== null) {
                $photosToDelete = $pet->photos()->whereIn('id', $data->deletePhotoIds)->get();

                foreach ($photosToDelete as $photo) {
                    Storage::disk('s3')->delete($photo->path);
                    $photo->delete();
                }
            }

            if ($data->newPhotos !== null) {
                $maxPosition = $pet->photos()->max('position') ?? -1;

                foreach ($data->newPhotos as $photo) {
                    $maxPosition++;
                    $converted = $this->imageConverter->toJpg($photo);

                    $path = $converted->storeAs(
                        'pets/photos',
                        Str::ulid().'.jpg',
                        's3',
                    );

                    $pet->photos()->create([
                        'path' => $path,
                        'position' => $maxPosition,
                    ]);
                }
            }

            if ($data->deletePhotoIds !== null || $data->newPhotos !== null) {
                $this->reindexPositions($pet);
            }

            if ($data->characteristicIds !== null) {
                $pet->characteristics()->sync($data->characteristicIds);
            }

            return $pet->load(['photos', 'characteristics', 'breed', 'secondaryBreed']);
        });
    }

    /**
     * Reindex photo positions to maintain a continuous sequence (0, 1, 2...).
     */
    private function reindexPositions(Pet $pet): void
    {
        $pet->photos()
            ->orderBy('position')
            ->get()
            ->each(function ($photo, $index): void {
                $photo->update(['position' => $index]);
            });
    }
}
