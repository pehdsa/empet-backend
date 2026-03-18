<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationSettingTest extends TestCase
{
    use RefreshDatabase;

    // ─── SHOW ───────────────────────────────────────────────

    public function test_show_returns_defaults_when_no_record_exists(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/user/notification-settings');

        $response->assertOk()
            ->assertJsonPath('data.notifyLostNearby', true)
            ->assertJsonPath('data.notifyMatches', true)
            ->assertJsonPath('data.notifySightings', true)
            ->assertJsonPath('data.nearbyRadiusKm', 5)
            ->assertJsonPath('data.location', null);

        $this->assertDatabaseMissing('user_notification_settings', [
            'user_id' => $user->id,
        ]);
    }

    public function test_show_returns_existing_settings_with_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $setting = UserNotificationSetting::factory()->create([
            'user_id' => $user->id,
            'notify_lost_nearby' => false,
            'nearby_radius_km' => 10,
        ]);

        DB::statement(
            'UPDATE user_notification_settings SET location = ST_MakePoint(?, ?)::geography WHERE id = ?',
            [-46.6333, -23.5505, $setting->id]
        );

        $response = $this->getJson('/api/v1/user/notification-settings');

        $response->assertOk()
            ->assertJsonPath('data.notifyLostNearby', false)
            ->assertJsonPath('data.nearbyRadiusKm', 10)
            ->assertJsonPath('data.location.latitude', -23.5505)
            ->assertJsonPath('data.location.longitude', -46.6333);
    }

    public function test_show_requires_authentication(): void
    {
        $this->getJson('/api/v1/user/notification-settings')
            ->assertUnauthorized();
    }

    // ─── UPDATE ─────────────────────────────────────────────

    public function test_update_creates_settings_if_not_exist(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/v1/user/notification-settings', [
            'notify_lost_nearby' => false,
            'nearby_radius_km' => 10,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.notifyLostNearby', false)
            ->assertJsonPath('data.nearbyRadiusKm', 10)
            ->assertJsonPath('data.notifyMatches', true)
            ->assertJsonPath('data.notifySightings', true);

        $this->assertDatabaseHas('user_notification_settings', [
            'user_id' => $user->id,
            'notify_lost_nearby' => false,
            'nearby_radius_km' => 10,
        ]);
    }

    public function test_update_partially_updates_existing_settings(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        UserNotificationSetting::factory()->create([
            'user_id' => $user->id,
            'notify_lost_nearby' => true,
            'notify_matches' => true,
            'nearby_radius_km' => 5,
        ]);

        $response = $this->putJson('/api/v1/user/notification-settings', [
            'notify_matches' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.notifyLostNearby', true)
            ->assertJsonPath('data.notifyMatches', false)
            ->assertJsonPath('data.nearbyRadiusKm', 5);
    }

    public function test_update_with_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/v1/user/notification-settings', [
            'latitude' => -23.5505,
            'longitude' => -46.6333,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.location.latitude', -23.5505)
            ->assertJsonPath('data.location.longitude', -46.6333);
    }

    public function test_update_latitude_without_longitude_returns_422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->putJson('/api/v1/user/notification-settings', [
            'latitude' => -23.5505,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude']);
    }

    public function test_update_clear_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $setting = UserNotificationSetting::factory()->create([
            'user_id' => $user->id,
        ]);

        DB::statement(
            'UPDATE user_notification_settings SET location = ST_MakePoint(?, ?)::geography WHERE id = ?',
            [-46.6333, -23.5505, $setting->id]
        );

        $response = $this->putJson('/api/v1/user/notification-settings', [
            'clear_location' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.location', null);
    }

    public function test_update_clear_location_with_coordinates_returns_422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->putJson('/api/v1/user/notification-settings', [
            'clear_location' => true,
            'latitude' => -23.5505,
            'longitude' => -46.6333,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_update_nearby_radius_zero_returns_422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->putJson('/api/v1/user/notification-settings', [
            'nearby_radius_km' => 0,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['nearby_radius_km']);
    }

    public function test_update_nearby_radius_above_max_returns_422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->putJson('/api/v1/user/notification-settings', [
            'nearby_radius_km' => 51,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['nearby_radius_km']);
    }

    public function test_update_requires_authentication(): void
    {
        $this->putJson('/api/v1/user/notification-settings', [
            'notify_lost_nearby' => false,
        ])->assertUnauthorized();
    }
}
