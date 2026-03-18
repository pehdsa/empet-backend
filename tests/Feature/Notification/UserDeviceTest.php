<?php

namespace Tests\Feature\Notification;

use App\Contracts\PushNotificationService;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class UserDeviceTest extends TestCase
{
    use RefreshDatabase;

    // ─── STORE ───────────────────────────────────────────────

    public function test_store_registers_new_device_with_provider(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('registerDevice')
                ->once()
                ->andReturn('onesignal-player-123');
        });

        $response = $this->postJson('/api/v1/user/devices', [
            'device_token' => 'fcm-token-abc',
            'platform' => 'IOS',
            'device_name' => 'iPhone 15',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.platform', 'IOS')
            ->assertJsonPath('data.deviceName', 'iPhone 15')
            ->assertJsonPath('data.isActive', true)
            ->assertJsonMissing(['deviceToken'])
            ->assertJsonMissing(['providerDeviceId']);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_token' => 'fcm-token-abc',
            'provider_device_id' => 'onesignal-player-123',
        ]);
    }

    public function test_store_upserts_existing_device_for_same_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('registerDevice')->once()->andReturn('new-player-id');
        });

        UserDevice::factory()->create([
            'user_id' => $user->id,
            'device_token' => 'existing-token',
            'device_name' => 'Old Name',
        ]);

        $response = $this->postJson('/api/v1/user/devices', [
            'device_token' => 'existing-token',
            'platform' => 'ANDROID',
            'device_name' => 'New Name',
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('user_devices', 1);
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_token' => 'existing-token',
            'device_name' => 'New Name',
        ]);
    }

    public function test_store_reassigns_token_from_another_user(): void
    {
        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();
        Sanctum::actingAs($newUser, ['*']);

        $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('registerDevice')->once()->andReturn('new-player-id');
        });

        UserDevice::factory()->create([
            'user_id' => $oldUser->id,
            'device_token' => 'shared-token',
            'provider_device_id' => 'old-player-id',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/user/devices', [
            'device_token' => 'shared-token',
            'platform' => 'ANDROID',
        ])->assertCreated();

        // After reassignment, the single device record now belongs to the new user
        $this->assertDatabaseCount('user_devices', 1);
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $newUser->id,
            'device_token' => 'shared-token',
            'is_active' => true,
            'provider_device_id' => 'new-player-id',
        ]);

        // Old user no longer has any active devices
        $this->assertEquals(0, $oldUser->devices()->where('is_active', true)->count());
    }

    public function test_store_with_provider_failure_saves_device_without_provider_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('registerDevice')
                ->once()
                ->andThrow(new \RuntimeException('Provider unavailable'));
        });

        $response = $this->postJson('/api/v1/user/devices', [
            'device_token' => 'fcm-token-xyz',
            'platform' => 'ANDROID',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_token' => 'fcm-token-xyz',
            'provider_device_id' => null,
        ]);
    }

    // ─── INDEX ───────────────────────────────────────────────

    public function test_index_lists_active_devices(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        UserDevice::factory()->count(2)->create(['user_id' => $user->id, 'is_active' => true]);
        UserDevice::factory()->inactive()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/user/devices');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        // Ensure sensitive fields are not exposed
        $response->assertJsonMissing(['deviceToken'])
            ->assertJsonMissing(['providerDeviceId']);
    }

    // ─── DESTROY ────────────────────────────────────────────

    public function test_destroy_deactivates_device(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('removeDevice')->once()->andReturn(true);
        });

        $device = UserDevice::factory()->create([
            'user_id' => $user->id,
            'provider_device_id' => 'player-123',
        ]);

        $response = $this->deleteJson("/api/v1/user/devices/{$device->id}");

        $response->assertOk()
            ->assertJsonPath('data.message', 'Device removed successfully.');

        $this->assertDatabaseHas('user_devices', [
            'id' => $device->id,
            'is_active' => false,
        ]);
    }

    public function test_destroy_deactivates_locally_even_if_provider_fails(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('removeDevice')
                ->once()
                ->andThrow(new \RuntimeException('Provider error'));
        });

        $device = UserDevice::factory()->create([
            'user_id' => $user->id,
            'provider_device_id' => 'player-456',
        ]);

        $this->deleteJson("/api/v1/user/devices/{$device->id}")
            ->assertOk();

        $this->assertDatabaseHas('user_devices', [
            'id' => $device->id,
            'is_active' => false,
        ]);
    }

    public function test_destroy_cannot_remove_other_users_device(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $device = UserDevice::factory()->create(['user_id' => $otherUser->id]);

        $this->deleteJson("/api/v1/user/devices/{$device->id}")
            ->assertNotFound();
    }

    // ─── VALIDATION ─────────────────────────────────────────

    public function test_store_invalid_platform_returns_422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/user/devices', [
            'device_token' => 'token',
            'platform' => 'WINDOWS',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/user/devices', [
            'device_token' => 'token',
            'platform' => 'IOS',
        ])->assertUnauthorized();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/user/devices')
            ->assertUnauthorized();
    }
}
