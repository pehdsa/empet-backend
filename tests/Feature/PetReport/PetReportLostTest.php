<?php

namespace Tests\Feature\PetReport;

use App\Enums\PetReportStatus;
use App\Enums\PetSize;
use App\Models\Pet;
use App\Models\PetReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetReportLostTest extends TestCase
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

    public function test_lost_returns_lost_reports_from_all_users(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet1 = Pet::factory()->create(['user_id' => $otherUser->id]);
        $pet2 = Pet::factory()->create(['user_id' => $user->id]);

        $this->createReportWithLocation(['pet_id' => $pet1->id, 'user_id' => $otherUser->id, 'status' => PetReportStatus::Lost]);
        $this->createReportWithLocation(['pet_id' => $pet2->id, 'user_id' => $user->id, 'status' => PetReportStatus::Lost]);

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_lost_returns_paginated_response(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $user->id, 'status' => PetReportStatus::Lost]);

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_lost_orders_by_distance_ascending(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $nearPet = Pet::factory()->create(['user_id' => $user->id]);
        $farPet = Pet::factory()->create(['user_id' => $user->id]);

        // Near: ~0km from reference
        $nearReport = $this->createReportWithLocation(
            ['pet_id' => $nearPet->id, 'user_id' => $user->id, 'status' => PetReportStatus::Lost],
            lng: -54.6156,
            lat: -20.4697
        );
        // Far: ~50km away
        $farReport = $this->createReportWithLocation(
            ['pet_id' => $farPet->id, 'user_id' => $user->id, 'status' => PetReportStatus::Lost],
            lng: -54.2000,
            lat: -20.2000
        );

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $nearReport->id);
        $response->assertJsonPath('data.1.id', $farReport->id);
    }

    public function test_lost_returns_distance_meters_in_response(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $this->createReportWithLocation(
            ['pet_id' => $pet->id, 'user_id' => $user->id, 'status' => PetReportStatus::Lost],
            lng: -54.6156,
            lat: -20.4697
        );

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156');

        $response->assertOk();
        $response->assertJsonStructure(['data' => [['distanceMeters']]]);
        $this->assertIsNumeric($response->json('data.0.distanceMeters'));
    }

    public function test_lost_excludes_found_and_cancelled_reports(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet1 = Pet::factory()->create(['user_id' => $user->id]);
        $pet2 = Pet::factory()->create(['user_id' => $user->id]);
        $pet3 = Pet::factory()->create(['user_id' => $user->id]);

        $this->createReportWithLocation(['pet_id' => $pet1->id, 'user_id' => $user->id, 'status' => PetReportStatus::Lost]);
        $this->createReportWithLocation(['pet_id' => $pet2->id, 'user_id' => $user->id, 'status' => PetReportStatus::Found, 'found_at' => now()]);
        $this->createReportWithLocation(['pet_id' => $pet3->id, 'user_id' => $user->id, 'status' => PetReportStatus::Cancelled]);

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'LOST');
    }

    public function test_lost_excludes_reports_with_soft_deleted_pet(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $activePet = Pet::factory()->create(['user_id' => $user->id]);
        $deletedPet = Pet::factory()->create(['user_id' => $user->id]);

        $this->createReportWithLocation(['pet_id' => $activePet->id, 'user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $deletedPet->id, 'user_id' => $user->id]);

        $deletedPet->delete();

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_lost_does_not_return_user_data(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $user->id]);

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156');

        $response->assertOk();
        $response->assertJsonMissing(['user' => []]);
    }

    // ──────────────────────────────────────────────
    // FILTERS
    // ──────────────────────────────────────────────

    public function test_lost_filters_by_species(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $dogPet = Pet::factory()->dog()->create(['user_id' => $user->id]);
        $catPet = Pet::factory()->cat()->create(['user_id' => $user->id]);

        $this->createReportWithLocation(['pet_id' => $dogPet->id, 'user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $catPet->id, 'user_id' => $user->id]);

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156&species=DOG');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.pet.species', 'DOG');
    }

    public function test_lost_filters_by_size(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $smallPet = Pet::factory()->create(['user_id' => $user->id, 'size' => PetSize::Small]);
        $largePet = Pet::factory()->create(['user_id' => $user->id, 'size' => PetSize::Large]);

        $this->createReportWithLocation(['pet_id' => $smallPet->id, 'user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $largePet->id, 'user_id' => $user->id]);

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156&size=SMALL');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.pet.size', 'SMALL');
    }

    // ──────────────────────────────────────────────
    // AUTH — 401
    // ──────────────────────────────────────────────

    public function test_lost_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156');

        $response->assertUnauthorized();
    }

    // ──────────────────────────────────────────────
    // VALIDATION — 422
    // ──────────────────────────────────────────────

    public function test_lost_returns_422_without_latitude(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pet-reports/lost?longitude=-54.6156');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['latitude']);
    }

    public function test_lost_returns_422_without_longitude(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['longitude']);
    }

    public function test_lost_returns_422_with_invalid_species(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pet-reports/lost?latitude=-20.4697&longitude=-54.6156&species=BIRD');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['species']);
    }
}
