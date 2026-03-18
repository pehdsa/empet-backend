<?php

namespace Database\Factories;

use App\Enums\DevicePlatform;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserDevice>
 */
class UserDeviceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_device_id' => Str::uuid()->toString(),
            'device_token' => Str::random(64),
            'platform' => fake()->randomElement(DevicePlatform::cases()),
            'device_name' => fake()->optional()->words(3, true),
            'is_active' => true,
            'last_active_at' => now(),
        ];
    }

    public function ios(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => DevicePlatform::Ios,
        ]);
    }

    public function android(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => DevicePlatform::Android,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withoutProvider(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_device_id' => null,
        ]);
    }
}
