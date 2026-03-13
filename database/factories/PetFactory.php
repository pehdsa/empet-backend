<?php

namespace Database\Factories;

use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pet>
 */
class PetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->firstName(),
            'species' => fake()->randomElement(PetSpecies::cases()),
            'size' => fake()->randomElement(PetSize::cases()),
            'sex' => fake()->randomElement(PetSex::cases()),
            'breed' => fake()->optional()->word(),
            'primary_color' => fake()->optional()->safeColorName(),
            'photo_url' => null,
            'notes' => fake()->optional()->sentence(),
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
