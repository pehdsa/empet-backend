<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotificationSetting>
 */
class UserNotificationSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notify_lost_nearby' => true,
            'notify_matches' => true,
            'notify_sightings' => true,
            'nearby_radius_km' => 5,
        ];
    }

    public function disabledLostNearby(): static
    {
        return $this->state(fn (array $attributes) => [
            'notify_lost_nearby' => false,
        ]);
    }

    public function disabledMatches(): static
    {
        return $this->state(fn (array $attributes) => [
            'notify_matches' => false,
        ]);
    }

    public function disabledSightings(): static
    {
        return $this->state(fn (array $attributes) => [
            'notify_sightings' => false,
        ]);
    }
}
