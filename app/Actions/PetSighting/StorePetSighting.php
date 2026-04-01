<?php

namespace App\Actions\PetSighting;

use App\DTOs\PetSighting\StorePetSightingData;
use App\Jobs\ProcessSightingMatching;
use App\Models\PetSighting;
use App\Models\User;
use App\Services\Image\ImageConverter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StorePetSighting
{
    public function __construct(
        private readonly ImageConverter $imageConverter,
    ) {}

    /**
     * Create a new independent pet sighting.
     */
    public function handle(StorePetSightingData $data, User $user): PetSighting
    {
        $sighting = DB::transaction(function () use ($data, $user): PetSighting {
            $sighting = PetSighting::create([
                'user_id' => $user->id,
                'title' => $data->title,
                'description' => $data->description,
                'address_hint' => $data->addressHint,
                'sighted_at' => $data->sightedAt,
                'species' => $data->species,
                'size' => $data->size,
                'sex' => $data->sex,
                'color' => $data->color,
                'breed_id' => $data->breedId,
                'share_phone' => $data->sharePhone,
                'location' => DB::raw(sprintf(
                    'ST_MakePoint(%s, %s)::geography',
                    (float) $data->longitude,
                    (float) $data->latitude,
                )),
            ]);

            foreach ($data->photos as $position => $photo) {
                $converted = $this->imageConverter->toJpg($photo);

                $path = $converted->storeAs(
                    'sightings/photos',
                    Str::ulid().'.jpg',
                    's3',
                );

                $sighting->photos()->create([
                    'path' => $path,
                    'position' => $position,
                ]);
            }

            if (! empty($data->characteristicIds)) {
                $sighting->characteristics()->sync($data->characteristicIds);
            }

            return $sighting->load(['photos', 'characteristics', 'breed', 'user']);
        });

        DB::afterCommit(function () use ($sighting) {
            ProcessSightingMatching::dispatch($sighting);
        });

        return $sighting;
    }
}
