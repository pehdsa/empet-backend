<?php

namespace Tests\Feature\PetReport;

use App\Enums\PetReportStatus;
use App\Models\Pet;
use App\Models\PetReport;
use App\Models\PetSighting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetReportDetailTest extends TestCase
{
    use RefreshDatabase;

    private function createReportWithLocation(array $attributes, float $lng = -54.6156, float $lat = -20.4697): PetReport
    {
        $report = PetReport::factory()->create($attributes);
        DB::statement(
            'UPDATE pet_reports SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$lng, $lat, $report->id]
        );

        return $report->fresh();
    }

    // ──────────────────────────────────────────────
    // HAPPY PATH — LOST
    // ──────────────────────────────────────────────

    public function test_detail_returns_lost_report_for_any_authenticated_user(): void
    {
        $owner = User::factory()->client()->create();
        $viewer = User::factory()->client()->create();
        Sanctum::actingAs($viewer, ['*']);

        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $owner->id, 'status' => PetReportStatus::Lost]);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/detail");

        $response->assertOk();
        $response->assertJsonPath('data.id', $report->id);
        $response->assertJsonPath('data.status', 'LOST');
    }

    public function test_detail_loads_pet_with_full_relationships(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $report = $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $user->id]);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/detail");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'pet' => ['id', 'name', 'species', 'size'],
            ],
        ]);
    }

    public function test_detail_includes_sightings_count(): void
    {
        $owner = User::factory()->client()->create();
        $viewer = User::factory()->client()->create();
        Sanctum::actingAs($viewer, ['*']);

        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $owner->id]);

        PetSighting::factory()->count(3)->create(['report_id' => $report->id, 'user_id' => $viewer->id]);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/detail");

        $response->assertOk();
        $response->assertJsonPath('data.sightingsCount', 3);
    }

    public function test_detail_does_not_load_user_or_matches(): void
    {
        $owner = User::factory()->client()->create();
        $viewer = User::factory()->client()->create();
        Sanctum::actingAs($viewer, ['*']);

        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $owner->id]);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/detail");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertArrayNotHasKey('user', $data);
        $this->assertArrayNotHasKey('matches', $data);
    }

    // ──────────────────────────────────────────────
    // HAPPY PATH — FOUND
    // ──────────────────────────────────────────────

    public function test_detail_returns_found_report_for_any_authenticated_user(): void
    {
        $owner = User::factory()->client()->create();
        $viewer = User::factory()->client()->create();
        Sanctum::actingAs($viewer, ['*']);

        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation([
            'pet_id' => $pet->id,
            'user_id' => $owner->id,
            'status' => PetReportStatus::Found,
            'found_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/detail");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'FOUND');
    }

    // ──────────────────────────────────────────────
    // AUTH — 403 for CANCELLED
    // ──────────────────────────────────────────────

    public function test_detail_returns_403_for_cancelled_report_when_not_owner(): void
    {
        $owner = User::factory()->client()->create();
        $viewer = User::factory()->client()->create();
        Sanctum::actingAs($viewer, ['*']);

        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation([
            'pet_id' => $pet->id,
            'user_id' => $owner->id,
            'status' => PetReportStatus::Cancelled,
        ]);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/detail");

        $response->assertForbidden();
    }

    public function test_detail_returns_200_for_cancelled_report_when_owner(): void
    {
        $owner = User::factory()->client()->create();
        Sanctum::actingAs($owner, ['*']);

        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation([
            'pet_id' => $pet->id,
            'user_id' => $owner->id,
            'status' => PetReportStatus::Cancelled,
        ]);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/detail");

        $response->assertOk();
    }

    // ──────────────────────────────────────────────
    // AUTH — 401
    // ──────────────────────────────────────────────

    public function test_detail_returns_401_for_unauthenticated(): void
    {
        $owner = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $owner->id]);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/detail");

        $response->assertUnauthorized();
    }
}
