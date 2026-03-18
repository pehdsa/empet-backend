<?php

namespace Database\Factories;

use App\Models\PetReport;
use App\Models\PetSighting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<PetSighting>
 */
class PetSightingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'report_id' => PetReport::factory(),
            'location' => DB::raw(sprintf(
                'ST_MakePoint(%s, %s)::geography',
                fake()->longitude(-47.0, -46.0),
                fake()->latitude(-24.0, -23.0),
            )),
            'address_hint' => fake()->optional()->address(),
            'description' => fake()->optional()->paragraph(),
            'sighted_at' => now(),
            'share_phone' => false,
            'is_active' => true,
        ];
    }

    public function withSharePhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'share_phone' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
