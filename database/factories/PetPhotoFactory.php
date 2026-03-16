<?php

namespace Database\Factories;

use App\Models\Pet;
use App\Models\PetPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PetPhoto>
 */
class PetPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pet_id' => Pet::factory(),
            'path' => 'pets/photos/'.fake()->uuid().'.jpg',
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
