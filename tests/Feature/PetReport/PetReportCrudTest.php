<?php

namespace Tests\Feature\PetReport;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Jobs\ProcessPetMatching;
use App\Models\Pet;
use App\Models\PetMatch;
use App\Models\PetReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetReportCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createReportWithLocation(array $attributes, float $lng = -43.1729, float $lat = -22.9068): PetReport
    {
        $report = PetReport::factory()->create($attributes);
        DB::statement('UPDATE pet_reports SET location = ST_MakePoint(?, ?)::geography WHERE id = ?', [$lng, $lat, $report->id]);

        return $report->fresh();
    }

    // ──────────────────────────────────────────────
    // STORE (POST /api/v1/pet-reports)
    // ──────────────────────────────────────────────

    public function test_store_creates_report_with_all_fields(): void
    {
        Queue::fake();

        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pet-reports', [
            'pet_id' => $pet->id,
            'latitude' => -22.9068,
            'longitude' => -43.1729,
            'address_hint' => 'Perto da praça',
            'description' => 'Fugiu pelo portão',
            'lost_at' => '2026-03-15 14:00:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'petId', 'userId', 'status', 'location', 'addressHint',
                    'description', 'lostAt', 'foundAt', 'isActive', 'pet', 'createdAt', 'updatedAt',
                ],
            ])
            ->assertJsonPath('data.status', 'LOST')
            ->assertJsonPath('data.isActive', true)
            ->assertJsonPath('data.addressHint', 'Perto da praça');
    }

    public function test_store_dispatches_matching_job(): void
    {
        Queue::fake();

        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/pet-reports', [
            'pet_id' => $pet->id,
            'latitude' => -22.9068,
            'longitude' => -43.1729,
            'lost_at' => now()->subHour()->toDateTimeString(),
        ]);

        Queue::assertPushed(ProcessPetMatching::class);
    }

    public function test_store_report_has_status_lost_and_is_active(): void
    {
        Queue::fake();

        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/pet-reports', [
            'pet_id' => $pet->id,
            'latitude' => -22.9068,
            'longitude' => -43.1729,
            'lost_at' => now()->subHour()->toDateTimeString(),
        ]);

        $this->assertDatabaseHas('pet_reports', [
            'pet_id' => $pet->id,
            'status' => PetReportStatus::Lost->value,
            'is_active' => true,
        ]);
    }

    public function test_store_returns_422_when_pet_already_has_active_lost_report(): void
    {
        Queue::fake();

        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        $this->createReportWithLocation([
            'pet_id' => $pet->id,
            'user_id' => $user->id,
            'status' => PetReportStatus::Lost,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pet-reports', [
            'pet_id' => $pet->id,
            'latitude' => -22.9068,
            'longitude' => -43.1729,
            'lost_at' => now()->subHour()->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pet_id']);
    }

    public function test_store_returns_422_when_pet_belongs_to_another_user(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pet-reports', [
            'pet_id' => $pet->id,
            'latitude' => -22.9068,
            'longitude' => -43.1729,
            'lost_at' => now()->subHour()->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pet_id']);
    }

    public function test_store_returns_422_when_pet_is_inactive(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->inactive()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pet-reports', [
            'pet_id' => $pet->id,
            'latitude' => -22.9068,
            'longitude' => -43.1729,
            'lost_at' => now()->subHour()->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pet_id']);
    }

    public function test_store_returns_422_when_pet_is_soft_deleted(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id, 'deleted_at' => now()]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pet-reports', [
            'pet_id' => $pet->id,
            'latitude' => -22.9068,
            'longitude' => -43.1729,
            'lost_at' => now()->subHour()->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pet_id']);
    }

    public function test_store_returns_422_when_lost_at_is_in_future(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pet-reports', [
            'pet_id' => $pet->id,
            'latitude' => -22.9068,
            'longitude' => -43.1729,
            'lost_at' => now()->addDay()->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lost_at']);
    }

    public function test_store_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/pet-reports', [
            'pet_id' => 1,
            'latitude' => -22.9068,
            'longitude' => -43.1729,
            'lost_at' => now()->subHour()->toDateTimeString(),
        ]);

        $response->assertStatus(401);
    }

    public function test_store_returns_422_when_required_fields_missing(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pet-reports', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pet_id', 'latitude', 'longitude', 'lost_at']);
    }

    // ──────────────────────────────────────────────
    // INDEX (GET /api/v1/pet-reports)
    // ──────────────────────────────────────────────

    public function test_index_client_lists_only_own_reports(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();

        $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $this->createReportWithLocation(['user_id' => $otherUser->id, 'pet_id' => Pet::factory()->create(['user_id' => $otherUser->id])->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pet-reports');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_index_admin_lists_all_and_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => $pet->id, 'status' => PetReportStatus::Lost]);
        $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id, 'status' => PetReportStatus::Found, 'found_at' => now()]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/pet-reports');
        $response->assertStatus(200)->assertJsonCount(2, 'data');

        $response = $this->getJson('/api/v1/pet-reports?status=LOST');
        $response->assertStatus(200)->assertJsonCount(1, 'data');

        $response = $this->getJson('/api/v1/pet-reports?pet_id='.$pet->id);
        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_index_filters_by_proximity(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        $nearReport = $this->createReportWithLocation(
            ['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id],
            -43.1729, -22.9068
        );
        $farReport = $this->createReportWithLocation(
            ['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id],
            -46.6333, -23.5505
        );

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/pet-reports?latitude=-22.9068&longitude=-43.1729&radius_km=10');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $nearReport->id);
    }

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/pet-reports');

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    // SHOW (GET /api/v1/pet-reports/{petReport})
    // ──────────────────────────────────────────────

    public function test_show_client_sees_own_report(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => $pet->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'petId', 'userId', 'status', 'location', 'pet', 'matchesCount'],
            ]);
    }

    public function test_show_location_returns_as_object(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(
            ['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id],
            -43.1729, -22.9068
        );

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}");

        $response->assertStatus(200);
        $location = $response->json('data.location');
        $this->assertNotNull($location['latitude']);
        $this->assertNotNull($location['longitude']);
    }

    public function test_show_admin_can_see_others_report(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}");

        $response->assertStatus(200);
    }

    public function test_show_client_cannot_see_others_report(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $otherUser->id, 'pet_id' => Pet::factory()->create(['user_id' => $otherUser->id])->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}");

        $response->assertStatus(403);
    }

    public function test_show_returns_401_when_unauthenticated(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}");

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    // UPDATE (PUT /api/v1/pet-reports/{petReport})
    // ──────────────────────────────────────────────

    public function test_update_happy_path(): void
    {
        Queue::fake();

        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pet-reports/{$report->id}", [
            'description' => 'Updated description',
            'address_hint' => 'Nova dica',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.description', 'Updated description');
    }

    public function test_update_cannot_update_cancelled_report(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation([
            'user_id' => $user->id,
            'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id,
            'status' => PetReportStatus::Cancelled,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pet-reports/{$report->id}", [
            'description' => 'test',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_cannot_update_found_report(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation([
            'user_id' => $user->id,
            'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id,
            'status' => PetReportStatus::Found,
            'found_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pet-reports/{$report->id}", [
            'description' => 'test',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_location_change_deletes_pending_keeps_dismissed_and_redispatches(): void
    {
        Queue::fake();

        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Pending]);
        PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Dismissed]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pet-reports/{$report->id}", [
            'latitude' => -23.5505,
            'longitude' => -46.6333,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('pet_matches', [
            'report_id' => $report->id,
            'status' => PetMatchStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('pet_matches', [
            'report_id' => $report->id,
            'status' => PetMatchStatus::Dismissed->value,
        ]);

        Queue::assertPushed(ProcessPetMatching::class);
    }

    public function test_update_client_cannot_update_others_report(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $otherUser->id, 'pet_id' => Pet::factory()->create(['user_id' => $otherUser->id])->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pet-reports/{$report->id}", [
            'description' => 'hacked',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_returns_401_when_unauthenticated(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        $response = $this->putJson("/api/v1/pet-reports/{$report->id}", [
            'description' => 'test',
        ]);

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    // CANCEL (PATCH /api/v1/pet-reports/{petReport}/cancel)
    // ──────────────────────────────────────────────

    public function test_cancel_changes_status_to_cancelled(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'CANCELLED');
    }

    public function test_cancel_dismisses_pending_matches(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Pending]);

        Sanctum::actingAs($user, ['*']);

        $this->patchJson("/api/v1/pet-reports/{$report->id}/cancel");

        $this->assertDatabaseHas('pet_matches', [
            'report_id' => $report->id,
            'status' => PetMatchStatus::Dismissed->value,
        ]);
    }

    public function test_cancel_cannot_cancel_already_cancelled(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation([
            'user_id' => $user->id,
            'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id,
            'status' => PetReportStatus::Cancelled,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_cancel_cannot_cancel_found(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation([
            'user_id' => $user->id,
            'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id,
            'status' => PetReportStatus::Found,
            'found_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_cancel_client_cannot_cancel_others_report(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $otherUser->id, 'pet_id' => Pet::factory()->create(['user_id' => $otherUser->id])->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_cancel_returns_401_when_unauthenticated(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/cancel");

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    // MARK FOUND (PATCH /api/v1/pet-reports/{petReport}/found)
    // ──────────────────────────────────────────────

    public function test_mark_found_sets_status_and_found_at(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/found");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'FOUND');

        $this->assertDatabaseHas('pet_reports', [
            'id' => $report->id,
            'status' => PetReportStatus::Found->value,
        ]);
        $this->assertNotNull($report->fresh()->found_at);
    }

    public function test_mark_found_with_confirmed_match(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $match1 = PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Pending]);
        $match2 = PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Pending]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/found", [
            'confirmed_match_id' => $match1->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('pet_matches', ['id' => $match1->id, 'status' => PetMatchStatus::Confirmed->value]);
        $this->assertDatabaseHas('pet_matches', ['id' => $match2->id, 'status' => PetMatchStatus::Dismissed->value]);
    }

    public function test_mark_found_without_match_dismisses_all(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        PetMatch::factory()->count(2)->create(['report_id' => $report->id, 'status' => PetMatchStatus::Pending]);

        Sanctum::actingAs($user, ['*']);

        $this->patchJson("/api/v1/pet-reports/{$report->id}/found");

        $this->assertEquals(0, PetMatch::where('report_id', $report->id)->where('status', PetMatchStatus::Pending)->count());
        $this->assertEquals(2, PetMatch::where('report_id', $report->id)->where('status', PetMatchStatus::Dismissed)->count());
    }

    public function test_mark_found_invalid_match_id_returns_422(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        $otherReport = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $otherMatch = PetMatch::factory()->create(['report_id' => $otherReport->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/found", [
            'confirmed_match_id' => $otherMatch->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_mark_found_cannot_mark_cancelled(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation([
            'user_id' => $user->id,
            'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id,
            'status' => PetReportStatus::Cancelled,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/found");

        $response->assertStatus(403);
    }

    public function test_mark_found_cannot_mark_already_found(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation([
            'user_id' => $user->id,
            'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id,
            'status' => PetReportStatus::Found,
            'found_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/found");

        $response->assertStatus(403);
    }

    public function test_mark_found_client_cannot_mark_others(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $otherUser->id, 'pet_id' => Pet::factory()->create(['user_id' => $otherUser->id])->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/found");

        $response->assertStatus(403);
    }

    public function test_mark_found_returns_401_when_unauthenticated(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/found");

        $response->assertStatus(401);
    }
}
