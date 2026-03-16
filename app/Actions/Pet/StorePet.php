<?php

namespace App\Actions\Pet;

use App\DTOs\Pet\StorePetData;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StorePet
{
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
                'breed' => $data->breed,
                'secondary_breed' => $data->secondaryBreed,
                'primary_color' => $data->primaryColor,
                'notes' => $data->notes,
            ]);

            foreach ($data->photos as $position => $photo) {
                $path = $photo->storeAs(
                    'pets/photos',
                    Str::ulid().'.'.$photo->getClientOriginalExtension(),
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

            return $pet->load(['photos', 'characteristics']);
        });
    }
}
