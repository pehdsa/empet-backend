<?php

namespace Database\Factories;

use App\Enums\PetSpecies;
use App\Models\Breed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Breed>
 */
class BreedFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'species' => fake()->randomElement(PetSpecies::cases()),
            'is_active' => true,
        ];
    }

    public function dog(): static
    {
        return $this->state(fn (array $attributes) => [
            'species' => PetSpecies::Dog,
        ]);
    }

    public function cat(): static
    {
        return $this->state(fn (array $attributes) => [
            'species' => PetSpecies::Cat,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
