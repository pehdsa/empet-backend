<?php

namespace Tests\Feature\PetReport;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Models\Pet;
use App\Models\PetMatch;
use App\Models\PetReport;
use App\Models\PetSighting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetReportMatchTest extends TestCase
{
    use RefreshDatabase;

    private function createReportWithLocation(array $attributes, float $lng = -43.1729, float $lat = -22.9068): PetReport
    {
        $report = PetReport::factory()->create($attributes);
        DB::statement('UPDATE pet_reports SET location = ST_MakePoint(?, ?)::geography WHERE id = ?', [$lng, $lat, $report->id]);

        return $report->fresh();
    }

    // ──────────────────────────────────────────────
    // MATCHES LIST (GET /api/v1/pet-reports/{petReport}/matches)
    // ──────────────────────────────────────────────

    public function test_matches_returns_pending_by_default_ordered_by_score_distance_id(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        $matchLow = PetMatch::factory()->create(['report_id' => $report->id, 'score' => 40, 'distance_meters' => 1000, 'status' => PetMatchStatus::Pending]);
        $matchHigh = PetMatch::factory()->create(['report_id' => $report->id, 'score' => 80, 'distance_meters' => 5000, 'status' => PetMatchStatus::Pending]);
        PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Dismissed]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/matches");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $matchHigh->id)
            ->assertJsonPath('data.1.id', $matchLow->id);
    }

    public function test_matches_filters_by_status(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Pending]);
        PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Dismissed]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/matches?status=DISMISSED");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'DISMISSED');
    }

    public function test_matches_includes_sighting_with_photos_and_characteristics(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        $sighting = PetSighting::factory()->create();
        PetMatch::factory()->create(['report_id' => $report->id, 'sighting_id' => $sighting->id, 'status' => PetMatchStatus::Pending]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/matches");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    ['id', 'reportId', 'sightingId', 'score', 'distanceMeters', 'status', 'sighting'],
                ],
            ]);
    }

    public function test_matches_client_cannot_view_others_report_matches(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $otherUser->id, 'pet_id' => Pet::factory()->create(['user_id' => $otherUser->id])->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/matches");

        $response->assertStatus(403);
    }

    public function test_matches_admin_can_view_others_report_matches(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/matches");

        $response->assertStatus(200);
    }

    public function test_matches_returns_401_when_unauthenticated(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);

        $response = $this->getJson("/api/v1/pet-reports/{$report->id}/matches");

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    // DISMISS MATCH (PATCH /api/v1/pet-reports/{petReport}/matches/{petMatch}/dismiss)
    // ──────────────────────────────────────────────

    public function test_dismiss_match_sets_status_to_dismissed(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $match = PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Pending]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/dismiss");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'DISMISSED');

        $this->assertDatabaseHas('pet_matches', ['id' => $match->id, 'status' => PetMatchStatus::Dismissed->value]);
    }

    public function test_dismiss_match_returns_422_when_already_dismissed(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $match = PetMatch::factory()->dismissed()->create(['report_id' => $report->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/dismiss");

        $response->assertStatus(422);
    }

    public function test_dismiss_match_returns_422_when_already_confirmed(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation([
            'user_id' => $user->id,
            'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id,
            'status' => PetReportStatus::Found,
            'found_at' => now(),
        ]);
        $match = PetMatch::factory()->confirmed()->create(['report_id' => $report->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/dismiss");

        $response->assertStatus(422);
    }

    public function test_dismiss_match_returns_404_when_match_belongs_to_other_report(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $otherReport = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $match = PetMatch::factory()->create(['report_id' => $otherReport->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/dismiss");

        $response->assertStatus(404);
    }

    public function test_dismiss_match_client_cannot_dismiss_others(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $otherUser->id, 'pet_id' => Pet::factory()->create(['user_id' => $otherUser->id])->id]);
        $match = PetMatch::factory()->create(['report_id' => $report->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/dismiss");

        $response->assertStatus(403);
    }

    public function test_dismiss_match_returns_401_when_unauthenticated(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $match = PetMatch::factory()->create(['report_id' => $report->id]);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/dismiss");

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    // CONFIRM MATCH (PATCH /api/v1/pet-reports/{petReport}/matches/{petMatch}/confirm)
    // ──────────────────────────────────────────────

    public function test_confirm_match_marks_confirmed_and_report_found(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $match = PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Pending]);
        $otherMatch = PetMatch::factory()->create(['report_id' => $report->id, 'status' => PetMatchStatus::Pending]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/confirm");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'CONFIRMED');

        $this->assertDatabaseHas('pet_reports', ['id' => $report->id, 'status' => PetReportStatus::Found->value]);
        $this->assertNotNull($report->fresh()->found_at);
        $this->assertDatabaseHas('pet_matches', ['id' => $match->id, 'status' => PetMatchStatus::Confirmed->value]);
        $this->assertDatabaseMissing('pet_matches', ['id' => $otherMatch->id]);
    }

    public function test_confirm_match_returns_422_when_already_dismissed(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $match = PetMatch::factory()->dismissed()->create(['report_id' => $report->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/confirm");

        $response->assertStatus(422);
    }

    public function test_confirm_match_returns_404_when_match_belongs_to_other_report(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $otherReport = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $match = PetMatch::factory()->create(['report_id' => $otherReport->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/confirm");

        $response->assertStatus(404);
    }

    public function test_confirm_match_client_cannot_confirm_others(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $otherUser->id, 'pet_id' => Pet::factory()->create(['user_id' => $otherUser->id])->id]);
        $match = PetMatch::factory()->create(['report_id' => $report->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/confirm");

        $response->assertStatus(403);
    }

    public function test_confirm_match_returns_401_when_unauthenticated(): void
    {
        $user = User::factory()->client()->create();
        $report = $this->createReportWithLocation(['user_id' => $user->id, 'pet_id' => Pet::factory()->create(['user_id' => $user->id])->id]);
        $match = PetMatch::factory()->create(['report_id' => $report->id]);

        $response = $this->patchJson("/api/v1/pet-reports/{$report->id}/matches/{$match->id}/confirm");

        $response->assertStatus(401);
    }
}
