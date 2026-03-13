<?php

namespace Database\Factories;

use App\Enums\CharacteristicCategory;
use App\Models\Characteristic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Characteristic>
 */
class CharacteristicFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'category' => fake()->randomElement(CharacteristicCategory::cases()),
            'is_active' => true,
        ];
    }

    public function marking(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => CharacteristicCategory::Marking,
        ]);
    }

    public function coat(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => CharacteristicCategory::Coat,
        ]);
    }

    public function behavior(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => CharacteristicCategory::Behavior,
        ]);
    }

    public function identification(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => CharacteristicCategory::Identification,
        ]);
    }
}
