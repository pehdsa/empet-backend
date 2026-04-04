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
        $score = fake()->randomFloat(2, 0, 100);

        return [
            'report_id' => PetReport::factory(),
            'sighting_id' => PetSighting::factory(),
            'base_score' => $score,
            'final_score' => $score,
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

    public function aiEvaluated(float $aiScore = 85.0, float $finalScore = 95.0, float $confidence = 0.9): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_score' => $aiScore,
            'ai_confidence' => $confidence,
            'ai_status' => 'SUCCESS',
            'ai_provider' => 'log',
            'ai_model' => 'log',
            'ai_summary' => fake()->sentence(8),
            'ai_evaluated_at' => now(),
            'final_score' => $finalScore,
        ]);
    }

    public function aiFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_status' => 'FAILED',
            'ai_provider' => 'log',
            'ai_model' => 'log',
            'ai_evaluated_at' => now(),
        ]);
    }
}
