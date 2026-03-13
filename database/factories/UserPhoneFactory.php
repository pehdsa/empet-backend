<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPhone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPhone>
 */
class UserPhoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => fake()->e164PhoneNumber(),
            'is_whatsapp' => false,
            'is_primary' => false,
            'label' => fake()->optional()->word(),
        ];
    }

    public function whatsapp(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_whatsapp' => true,
        ]);
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
