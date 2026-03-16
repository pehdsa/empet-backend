<?php

namespace Database\Factories;

use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use App\Models\Breed;
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
            'breed_id' => null,
            'secondary_breed_id' => null,
            'breed_description' => null,
            'primary_color' => fake()->optional()->safeColorName(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Pet $pet): void {
            if ($pet->breed_id === null) {
                $breed = Breed::factory()->create(['species' => $pet->species]);
                $pet->update(['breed_id' => $breed->id]);
            }
        });
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

    public function withSecondaryBreed(): static
    {
        return $this->afterCreating(function (Pet $pet): void {
            $breed = Breed::factory()->create(['species' => $pet->species]);
            $pet->update(['secondary_breed_id' => $breed->id]);
        });
    }

    public function withBreedDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'breed_description' => fake()->sentence(3),
        ]);
    }
}
