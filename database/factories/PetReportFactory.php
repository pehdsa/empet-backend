<?php

namespace Database\Factories;

use App\Enums\PetReportStatus;
use App\Models\Pet;
use App\Models\PetReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PetReport>
 */
class PetReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pet_id' => Pet::factory(),
            'user_id' => User::factory(),
            'status' => PetReportStatus::Lost,
            'address_hint' => fake()->optional()->address(),
            'description' => fake()->optional()->paragraph(),
            'lost_at' => now(),
            'found_at' => null,
            'is_active' => true,
        ];
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PetReportStatus::Lost,
            'lost_at' => now(),
            'found_at' => null,
        ]);
    }

    public function found(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PetReportStatus::Found,
            'lost_at' => null,
            'found_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PetReportStatus::Cancelled,
        ]);
    }
}
