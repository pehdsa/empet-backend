<?php

namespace Database\Factories;

use App\Models\PetSighting;
use App\Models\PetSightingPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PetSightingPhoto>
 */
class PetSightingPhotoFactory extends Factory
{
    protected $model = PetSightingPhoto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pet_sighting_id' => PetSighting::factory(),
            'path' => 'sightings/photos/'.fake()->uuid().'.jpg',
            'position' => 0,
        ];
    }

    public function position(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
