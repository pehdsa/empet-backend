<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createNotification(User $user, array $overrides = []): string
    {
        $id = Str::uuid()->toString();

        $user->notifications()->create(array_merge([
            'id' => $id,
            'type' => 'App\Notifications\PetMatchesFound',
            'data' => ['report_id' => 1, 'pet_name' => 'Rex', 'matches_count' => 2],
            'read_at' => null,
        ], $overrides));

        return $id;
    }

    // ─── INDEX ───────────────────────────────────────────────

    public function test_index_returns_paginated_notifications_ordered_by_created_at_desc(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $id1 = $this->createNotification($user, ['created_at' => now()->subMinutes(2)]);
        $id2 = $this->createNotification($user, ['created_at' => now()->subMinute()]);
        $id3 = $this->createNotification($user, ['created_at' => now()]);

        $response = $this->getJson('/api/v1/user/notifications');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $id3)
            ->assertJsonPath('data.1.id', $id2)
            ->assertJsonPath('data.2.id', $id1);
    }

    public function test_index_filters_unread_only(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->createNotification($user, ['read_at' => now()]);
        $unreadId = $this->createNotification($user);

        $response = $this->getJson('/api/v1/user/notifications?unread=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unreadId);
    }

    public function test_index_maps_notification_types(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->createNotification($user, ['type' => 'App\Notifications\PetMatchesFound']);
        $this->createNotification($user, ['type' => 'App\Notifications\PetLostNearby']);
        $this->createNotification($user, ['type' => 'App\Notifications\PetSightingReported']);

        $response = $this->getJson('/api/v1/user/notifications');

        $types = collect($response->json('data'))->pluck('type')->sort()->values()->all();
        $this->assertEquals(['matches_found', 'pet_lost_nearby', 'pet_sighting_reported'], $types);
    }

    // ─── MARK AS READ ───────────────────────────────────────

    public function test_mark_as_read(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $id = $this->createNotification($user);

        $response = $this->patchJson("/api/v1/user/notifications/{$id}/read");

        $response->assertOk()
            ->assertJsonPath('id', $id);

        $this->assertNotNull($response->json('readAt'));
    }

    public function test_cannot_mark_other_users_notification(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $id = $this->createNotification($otherUser);

        $this->patchJson("/api/v1/user/notifications/{$id}/read")
            ->assertNotFound();
    }

    // ─── MARK ALL AS READ ───────────────────────────────────

    public function test_mark_all_as_read(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->createNotification($user);
        $this->createNotification($user);

        $response = $this->patchJson('/api/v1/user/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('message', 'All notifications marked as read.');

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    // ─── UNREAD COUNT ───────────────────────────────────────

    public function test_unread_count(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->createNotification($user);
        $this->createNotification($user);
        $this->createNotification($user, ['read_at' => now()]);

        $response = $this->getJson('/api/v1/user/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('unreadCount', 2);
    }

    // ─── AUTH ───────────────────────────────────────────────

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/user/notifications')
            ->assertUnauthorized();
    }

    public function test_mark_as_read_requires_authentication(): void
    {
        $this->patchJson('/api/v1/user/notifications/some-id/read')
            ->assertUnauthorized();
    }

    public function test_unread_count_requires_authentication(): void
    {
        $this->getJson('/api/v1/user/notifications/unread-count')
            ->assertUnauthorized();
    }
}
