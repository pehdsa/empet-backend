<?php

namespace Database\Factories;

use App\Enums\PetSpecies;
use App\Models\PetSighting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<PetSighting>
 */
class PetSightingFactory extends Factory
{
    protected $model = PetSighting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'address_hint' => fake()->optional()->address(),
            'sighted_at' => now(),
            'species' => fake()->randomElement(PetSpecies::cases()),
            'size' => null,
            'sex' => null,
            'color' => null,
            'breed_id' => null,
            'share_phone' => false,
            'location' => DB::raw(sprintf(
                'ST_MakePoint(%s, %s)::geography',
                fake()->longitude(-47.0, -46.0),
                fake()->latitude(-24.0, -23.0),
            )),
        ];
    }

    public function withSharePhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'share_phone' => true,
        ]);
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
}
