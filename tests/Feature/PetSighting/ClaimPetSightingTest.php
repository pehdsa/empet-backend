<?php

namespace Tests\Feature\PetSighting;

use App\Models\PetSighting;
use App\Models\User;
use App\Models\UserPhone;
use App\Notifications\PetSightingClaimed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClaimPetSightingTest extends TestCase
{
    use RefreshDatabase;

    // ─── HAPPY PATH ─────────────────────────────────────────

    public function test_claim_creates_record_and_returns_owner_data(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->withSharePhone()->create(['user_id' => $owner->id]);

        $ownerPhone = UserPhone::factory()->primary()->whatsapp()->create([
            'user_id' => $owner->id,
            'phone' => '+5511999999999',
        ]);

        $claimer = User::factory()->create();
        Sanctum::actingAs($claimer, ['*']);

        $response = $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");

        $response->assertOk()
            ->assertJsonPath('data.sightingId', $sighting->id)
            ->assertJsonPath('data.sightingOwner.name', $owner->name)
            ->assertJsonPath('data.sightingOwner.phone', '+5511999999999')
            ->assertJsonPath('data.sightingOwner.phoneIsWhatsapp', true);

        $this->assertDatabaseHas('pet_sighting_claims', [
            'pet_sighting_id' => $sighting->id,
            'user_id' => $claimer->id,
        ]);
    }

    public function test_claim_dispatches_notification_to_sighting_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->create(['user_id' => $owner->id]);

        $claimer = User::factory()->create();
        Sanctum::actingAs($claimer, ['*']);

        $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");

        Notification::assertSentTo($owner, PetSightingClaimed::class);
    }

    public function test_claim_notification_includes_claimer_data(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->create(['user_id' => $owner->id]);

        $claimer = User::factory()->create(['name' => 'Maria Silva']);
        UserPhone::factory()->primary()->whatsapp()->create([
            'user_id' => $claimer->id,
            'phone' => '+5511888888888',
        ]);
        Sanctum::actingAs($claimer, ['*']);

        $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");

        Notification::assertSentTo($owner, PetSightingClaimed::class, function ($notification) use ($owner) {
            $data = $notification->toArray($owner);

            return $data['claimer_name'] === 'Maria Silva'
                && $data['claimer_phone'] === '+5511888888888'
                && $data['claimer_phone_is_whatsapp'] === true
                && ! array_key_exists('claimer_email', $data);
        });
    }

    public function test_claim_notification_includes_sighting_title(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Cachorro visto no parque',
        ]);

        $claimer = User::factory()->create();
        Sanctum::actingAs($claimer, ['*']);

        $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");

        Notification::assertSentTo($owner, PetSightingClaimed::class, function ($notification) use ($owner) {
            $data = $notification->toArray($owner);

            return $data['sighting_title'] === 'Cachorro visto no parque';
        });
    }

    // ─── IDEMPOTENCY ────────────────────────────────────────

    public function test_claim_is_idempotent_returns_same_data_on_second_call(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->withSharePhone()->create(['user_id' => $owner->id]);
        UserPhone::factory()->primary()->create([
            'user_id' => $owner->id,
            'phone' => '+5511999999999',
        ]);

        $claimer = User::factory()->create();
        Sanctum::actingAs($claimer, ['*']);

        $first = $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");
        $second = $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");

        $first->assertOk();
        $second->assertOk();

        $this->assertEquals($first->json('data'), $second->json('data'));

        $this->assertDatabaseCount('pet_sighting_claims', 1);
    }

    public function test_claim_does_not_re_notify_on_second_call(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->create(['user_id' => $owner->id]);

        $claimer = User::factory()->create();
        Sanctum::actingAs($claimer, ['*']);

        $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");
        $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");

        Notification::assertSentToTimes($owner, PetSightingClaimed::class, 1);
    }

    // ─── SHARE PHONE ────────────────────────────────────────

    public function test_claim_returns_null_phone_when_share_phone_is_false(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->create([
            'user_id' => $owner->id,
            'share_phone' => false,
        ]);
        UserPhone::factory()->primary()->create(['user_id' => $owner->id]);

        $claimer = User::factory()->create();
        Sanctum::actingAs($claimer, ['*']);

        $response = $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");

        $response->assertOk()
            ->assertJsonPath('data.sightingOwner.phone', null)
            ->assertJsonPath('data.sightingOwner.phoneIsWhatsapp', null);
    }

    public function test_claim_returns_null_phone_when_owner_has_no_primary_phone(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->withSharePhone()->create(['user_id' => $owner->id]);

        $claimer = User::factory()->create();
        Sanctum::actingAs($claimer, ['*']);

        $response = $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");

        $response->assertOk()
            ->assertJsonPath('data.sightingOwner.phone', null)
            ->assertJsonPath('data.sightingOwner.phoneIsWhatsapp', null);
    }

    // ─── CLAIMER WITHOUT PHONE ──────────────────────────────

    public function test_claim_works_when_claimer_has_no_phone(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->create(['user_id' => $owner->id]);

        $claimer = User::factory()->create();
        Sanctum::actingAs($claimer, ['*']);

        $response = $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim");

        $response->assertOk();

        Notification::assertSentTo($owner, PetSightingClaimed::class, function ($notification) use ($owner) {
            $data = $notification->toArray($owner);

            return $data['claimer_phone'] === null;
        });
    }

    // ─── AUTH (401) ─────────────────────────────────────────

    public function test_claim_requires_authentication(): void
    {
        $sighting = PetSighting::factory()->create();

        $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim")
            ->assertUnauthorized();
    }

    // ─── FORBIDDEN (403) ────────────────────────────────────

    public function test_claim_forbidden_for_own_sighting(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner, ['*']);

        $sighting = PetSighting::factory()->create(['user_id' => $owner->id]);

        $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim")
            ->assertForbidden();
    }

    // ─── NOT FOUND (404) ────────────────────────────────────

    public function test_claim_returns_404_for_nonexistent_sighting(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/pet-sightings/99999/claim')
            ->assertNotFound();
    }

    public function test_claim_returns_404_for_soft_deleted_sighting(): void
    {
        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->create(['user_id' => $owner->id]);
        $sighting->delete();

        $claimer = User::factory()->create();
        Sanctum::actingAs($claimer, ['*']);

        $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim")
            ->assertNotFound();
    }

    // ─── MULTIPLE CLAIMERS ──────────────────────────────────

    public function test_different_users_can_claim_same_sighting(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sighting = PetSighting::factory()->create(['user_id' => $owner->id]);

        $claimer1 = User::factory()->create();
        $claimer2 = User::factory()->create();

        Sanctum::actingAs($claimer1, ['*']);
        $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim")->assertOk();

        Sanctum::actingAs($claimer2, ['*']);
        $this->postJson("/api/v1/pet-sightings/{$sighting->id}/claim")->assertOk();

        $this->assertDatabaseCount('pet_sighting_claims', 2);

        Notification::assertSentToTimes($owner, PetSightingClaimed::class, 2);
    }
}
