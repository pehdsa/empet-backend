<?php

namespace Tests\Feature\PetReport;

use App\Enums\PetReportStatus;
use App\Models\Pet;
use App\Models\PetReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetReportFoundTest extends TestCase
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
    // HAPPY PATH
    // ──────────────────────────────────────────────

    public function test_found_returns_found_reports_from_all_users(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet1 = Pet::factory()->create(['user_id' => $otherUser->id]);
        $pet2 = Pet::factory()->create(['user_id' => $user->id]);

        $this->createReportWithLocation([
            'pet_id' => $pet1->id,
            'user_id' => $otherUser->id,
            'status' => PetReportStatus::Found,
            'found_at' => now()->subDay(),
        ]);
        $this->createReportWithLocation([
            'pet_id' => $pet2->id,
            'user_id' => $user->id,
            'status' => PetReportStatus::Found,
            'found_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/pet-reports/found');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_found_excludes_lost_and_cancelled_reports(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet1 = Pet::factory()->create(['user_id' => $user->id]);
        $pet2 = Pet::factory()->create(['user_id' => $user->id]);
        $pet3 = Pet::factory()->create(['user_id' => $user->id]);

        $this->createReportWithLocation(['pet_id' => $pet1->id, 'user_id' => $user->id, 'status' => PetReportStatus::Found, 'found_at' => now()]);
        $this->createReportWithLocation(['pet_id' => $pet2->id, 'user_id' => $user->id, 'status' => PetReportStatus::Lost]);
        $this->createReportWithLocation(['pet_id' => $pet3->id, 'user_id' => $user->id, 'status' => PetReportStatus::Cancelled]);

        $response = $this->getJson('/api/v1/pet-reports/found');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'FOUND');
    }

    public function test_found_returns_paginated_response(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $user->id, 'status' => PetReportStatus::Found, 'found_at' => now()]);

        $response = $this->getJson('/api/v1/pet-reports/found');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_found_orders_by_found_at_desc(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet1 = Pet::factory()->create(['user_id' => $user->id]);
        $pet2 = Pet::factory()->create(['user_id' => $user->id]);

        $olderReport = $this->createReportWithLocation([
            'pet_id' => $pet1->id,
            'user_id' => $user->id,
            'status' => PetReportStatus::Found,
            'found_at' => now()->subDays(5),
        ]);
        $newerReport = $this->createReportWithLocation([
            'pet_id' => $pet2->id,
            'user_id' => $user->id,
            'status' => PetReportStatus::Found,
            'found_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/pet-reports/found');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $newerReport->id);
        $response->assertJsonPath('data.1.id', $olderReport->id);
    }

    public function test_found_excludes_reports_with_soft_deleted_pet(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $activePet = Pet::factory()->create(['user_id' => $user->id]);
        $deletedPet = Pet::factory()->create(['user_id' => $user->id]);

        $this->createReportWithLocation(['pet_id' => $activePet->id, 'user_id' => $user->id, 'status' => PetReportStatus::Found, 'found_at' => now()]);
        $this->createReportWithLocation(['pet_id' => $deletedPet->id, 'user_id' => $user->id, 'status' => PetReportStatus::Found, 'found_at' => now()]);

        $deletedPet->delete();

        $response = $this->getJson('/api/v1/pet-reports/found');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    // ──────────────────────────────────────────────
    // AUTH — 401
    // ──────────────────────────────────────────────

    public function test_found_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/pet-reports/found');

        $response->assertUnauthorized();
    }
}
