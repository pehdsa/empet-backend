<?php

namespace Tests\Feature\PetSighting;

use App\Models\Pet;
use App\Models\PetReport;
use App\Models\PetSighting;
use App\Models\User;
use App\Models\UserPhone;
use App\Notifications\PetSightingReported;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetSightingTest extends TestCase
{
    use RefreshDatabase;

    private function createLostReportWithLocation(User $owner, float $lat = -23.5505, float $lng = -46.6333): PetReport
    {
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = PetReport::factory()->lost()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
        ]);

        DB::statement(
            'UPDATE pet_reports SET location = ST_MakePoint(?, ?)::geography WHERE id = ?',
            [$lng, $lat, $report->id]
        );

        return $report;
    }

    // ─── STORE ───────────────────────────────────────────────

    public function test_store_creates_sighting_for_lost_report(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $sighter = User::factory()->create();
        Sanctum::actingAs($sighter, ['*']);

        $response = $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'address_hint' => 'Near the park',
            'description' => 'Saw a dog matching the description',
            'sighted_at' => now()->subHour()->toIso8601String(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.reportId', $report->id)
            ->assertJsonPath('data.userId', $sighter->id)
            ->assertJsonPath('data.addressHint', 'Near the park');

        $this->assertDatabaseHas('pet_sightings', [
            'report_id' => $report->id,
            'user_id' => $sighter->id,
        ]);
    }

    public function test_store_dispatches_notification_to_report_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $sighter = User::factory()->create();
        Sanctum::actingAs($sighter, ['*']);

        $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
        ])->assertCreated();

        Notification::assertSentTo($owner, PetSightingReported::class);
    }

    public function test_store_with_share_phone_exposes_contact_to_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $sighter = User::factory()->create();
        UserPhone::factory()->primary()->create([
            'user_id' => $sighter->id,
            'phone' => '+5511999999999',
        ]);

        // First: sighter creates the sighting
        Sanctum::actingAs($sighter, ['*']);
        $response = $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
            'share_phone' => true,
        ]);
        $response->assertCreated();
        $sightingId = $response->json('data.id');

        // Then: owner views the sighting and sees contactPhone
        Sanctum::actingAs($owner, ['*']);
        $showResponse = $this->getJson("/api/v1/pet-reports/{$report->id}/sightings/{$sightingId}");

        $showResponse->assertOk()
            ->assertJsonPath('data.contactPhone', '+5511999999999');
    }

    public function test_store_without_share_phone_does_not_expose_contact(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $sighter = User::factory()->create();
        UserPhone::factory()->primary()->create(['user_id' => $sighter->id]);

        Sanctum::actingAs($sighter, ['*']);
        $response = $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
            'share_phone' => false,
        ]);
        $sightingId = $response->json('data.id');

        Sanctum::actingAs($owner, ['*']);
        $showResponse = $this->getJson("/api/v1/pet-reports/{$report->id}/sightings/{$sightingId}");

        $showResponse->assertOk()
            ->assertJsonMissing(['contactPhone']);
    }

    public function test_contact_phone_only_visible_to_report_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $sighter = User::factory()->create();
        UserPhone::factory()->primary()->create([
            'user_id' => $sighter->id,
            'phone' => '+5511888888888',
        ]);

        Sanctum::actingAs($sighter, ['*']);
        $response = $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
            'share_phone' => true,
        ]);
        $sightingId = $response->json('data.id');

        // Another user (not owner) should NOT see contactPhone
        $otherUser = User::factory()->admin()->create();
        Sanctum::actingAs($otherUser, ['*']);

        $showResponse = $this->getJson("/api/v1/pet-reports/{$report->id}/sightings/{$sightingId}");
        $showResponse->assertOk()
            ->assertJsonMissing(['contactPhone']);
    }

    public function test_contact_phone_not_shown_when_sighter_has_no_primary_phone(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $sighter = User::factory()->create();
        // No primary phone created

        Sanctum::actingAs($sighter, ['*']);
        $response = $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
            'share_phone' => true,
        ]);
        $sightingId = $response->json('data.id');

        Sanctum::actingAs($owner, ['*']);
        $showResponse = $this->getJson("/api/v1/pet-reports/{$report->id}/sightings/{$sightingId}");

        $showResponse->assertOk()
            ->assertJsonPath('data.contactPhone', null);
    }

    // ─── VALIDATION ─────────────────────────────────────────

    public function test_cannot_sight_own_pet(): void
    {
        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        Sanctum::actingAs($owner, ['*']);

        $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['report']);
    }

    public function test_cannot_sight_cancelled_report(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = PetReport::factory()->cancelled()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
        ]);

        $sighter = User::factory()->create();
        Sanctum::actingAs($sighter, ['*']);

        $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['report']);
    }

    public function test_cannot_sight_found_report(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = PetReport::factory()->found()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
        ]);

        $sighter = User::factory()->create();
        Sanctum::actingAs($sighter, ['*']);

        $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['report']);
    }

    public function test_cannot_sight_inactive_report(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = PetReport::factory()->lost()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'is_active' => false,
        ]);

        $sighter = User::factory()->create();
        Sanctum::actingAs($sighter, ['*']);

        $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['report']);
    }

    // ─── DOUBLE SUBMIT ──────────────────────────────────────

    public function test_double_submit_returns_existing_sighting(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $sighter = User::factory()->create();
        Sanctum::actingAs($sighter, ['*']);

        $payload = [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
        ];

        $first = $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", $payload);
        $first->assertCreated();

        $second = $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", $payload);
        $second->assertOk(); // 200, not 201

        $this->assertDatabaseCount('pet_sightings', 1);
    }

    public function test_double_submit_does_not_dispatch_notification(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $sighter = User::factory()->create();
        Sanctum::actingAs($sighter, ['*']);

        $payload = [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
        ];

        $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", $payload);
        Notification::assertSentToTimes($owner, PetSightingReported::class, 1);

        $this->postJson("/api/v1/pet-reports/{$report->id}/sightings", $payload);
        Notification::assertSentToTimes($owner, PetSightingReported::class, 1);
    }

    // ─── INDEX ───────────────────────────────────────────────

    public function test_index_lists_sightings_for_report_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $sighter = User::factory()->create();
        PetSighting::factory()->create([
            'user_id' => $sighter->id,
            'report_id' => $report->id,
        ]);

        Sanctum::actingAs($owner, ['*']);

        $this->getJson("/api/v1/pet-reports/{$report->id}/sightings")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_forbidden_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser, ['*']);

        $this->getJson("/api/v1/pet-reports/{$report->id}/sightings")
            ->assertForbidden();
    }

    // ─── SHOW ───────────────────────────────────────────────

    public function test_show_returns_sighting_detail(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReportWithLocation($owner);

        $sighter = User::factory()->create();
        $sighting = PetSighting::factory()->create([
            'user_id' => $sighter->id,
            'report_id' => $report->id,
        ]);

        Sanctum::actingAs($owner, ['*']);

        $this->getJson("/api/v1/pet-reports/{$report->id}/sightings/{$sighting->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $sighting->id)
            ->assertJsonPath('data.reportId', $report->id);
    }

    // ─── AUTH ───────────────────────────────────────────────

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/pet-reports/1/sightings', [
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
        ])->assertUnauthorized();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/pet-reports/1/sightings')
            ->assertUnauthorized();
    }
}
