<?php

namespace Database\Factories;

use App\Enums\PetMatchStatus;
use App\Models\PetMatch;
use App\Models\PetReport;
use App\Models\PetSighting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PetMatch>
 */
class PetMatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_id' => PetReport::factory(),
            'sighting_id' => PetSighting::factory(),
            'score' => fake()->randomFloat(2, 0, 100),
            'distance_meters' => fake()->randomFloat(2, 0, 50000),
            'status' => PetMatchStatus::Pending,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PetMatchStatus::Confirmed,
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PetMatchStatus::Dismissed,
        ]);
    }
}
