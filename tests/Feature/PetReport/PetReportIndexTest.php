<?php

namespace Tests\Feature\PetReport;

use App\Enums\PetSize;
use App\Models\Pet;
use App\Models\PetReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetReportIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createReportWithLocation(array $attributes, float $lng = -43.1729, float $lat = -22.9068): PetReport
    {
        $report = PetReport::factory()->create($attributes);
        DB::statement('UPDATE pet_reports SET location = ST_MakePoint(?, ?)::geography WHERE id = ?', [$lng, $lat, $report->id]);

        return $report->fresh();
    }

    // ──────────────────────────────────────────────
    // FILTERS — species
    // ──────────────────────────────────────────────

    public function test_index_filters_by_species(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $dogPet = Pet::factory()->dog()->create(['user_id' => $user->id]);
        $catPet = Pet::factory()->cat()->create(['user_id' => $user->id]);

        $this->createReportWithLocation(['pet_id' => $dogPet->id, 'user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $catPet->id, 'user_id' => $user->id]);

        $response = $this->getJson('/api/v1/pet-reports?species=DOG');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.pet.species', 'DOG');
    }

    // ──────────────────────────────────────────────
    // FILTERS — size
    // ──────────────────────────────────────────────

    public function test_index_filters_by_size(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $smallPet = Pet::factory()->create(['user_id' => $user->id, 'size' => PetSize::Small]);
        $largePet = Pet::factory()->create(['user_id' => $user->id, 'size' => PetSize::Large]);

        $this->createReportWithLocation(['pet_id' => $smallPet->id, 'user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $largePet->id, 'user_id' => $user->id]);

        $response = $this->getJson('/api/v1/pet-reports?size=SMALL');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.pet.size', 'SMALL');
    }

    // ──────────────────────────────────────────────
    // FILTERS — species + size combined
    // ──────────────────────────────────────────────

    public function test_index_filters_by_species_and_size(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $dogSmall = Pet::factory()->dog()->create(['user_id' => $user->id, 'size' => PetSize::Small]);
        $dogLarge = Pet::factory()->dog()->create(['user_id' => $user->id, 'size' => PetSize::Large]);
        $catSmall = Pet::factory()->cat()->create(['user_id' => $user->id, 'size' => PetSize::Small]);

        $this->createReportWithLocation(['pet_id' => $dogSmall->id, 'user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $dogLarge->id, 'user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $catSmall->id, 'user_id' => $user->id]);

        $response = $this->getJson('/api/v1/pet-reports?species=DOG&size=SMALL');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.pet.species', 'DOG');
        $response->assertJsonPath('data.0.pet.size', 'SMALL');
    }

    // ──────────────────────────────────────────────
    // FILTERS — combined with status + geolocation
    // ──────────────────────────────────────────────

    public function test_index_filters_by_status_species_and_geolocation(): void
    {
        $user = User::factory()->admin()->create();
        Sanctum::actingAs($user, ['*']);

        $owner = User::factory()->client()->create();

        $dogPet = Pet::factory()->dog()->create(['user_id' => $owner->id]);
        $catPet = Pet::factory()->cat()->create(['user_id' => $owner->id]);

        // Dog report LOST near reference point
        $this->createReportWithLocation(
            ['pet_id' => $dogPet->id, 'user_id' => $owner->id, 'status' => 'LOST'],
            lng: -43.1729, lat: -22.9068
        );

        // Cat report LOST near reference point
        $this->createReportWithLocation(
            ['pet_id' => $catPet->id, 'user_id' => $owner->id, 'status' => 'LOST'],
            lng: -43.1730, lat: -22.9069
        );

        $response = $this->getJson('/api/v1/pet-reports?status=LOST&species=DOG&latitude=-22.9068&longitude=-43.1729&radius_km=10&paginate=false');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.pet.species', 'DOG');
    }

    // ──────────────────────────────────────────────
    // PAGINATION — paginate=false
    // ──────────────────────────────────────────────

    public function test_index_with_paginate_false_returns_collection(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $user->id]);

        $response = $this->getJson('/api/v1/pet-reports?paginate=false');

        $response->assertOk();
        $response->assertJsonStructure(['data']);
        $response->assertJsonMissing(['meta']);
        $response->assertJsonMissing(['links']);
    }

    public function test_index_with_paginate_true_returns_paginated(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $user->id]);

        $response = $this->getJson('/api/v1/pet-reports?paginate=true');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_without_paginate_param_returns_paginated(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $this->createReportWithLocation(['pet_id' => $pet->id, 'user_id' => $user->id]);

        $response = $this->getJson('/api/v1/pet-reports');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_with_paginate_false_and_geolocation_preserves_order(): void
    {
        $user = User::factory()->admin()->create();
        Sanctum::actingAs($user, ['*']);

        $owner = User::factory()->client()->create();

        $farPet = Pet::factory()->create(['user_id' => $owner->id]);
        $nearPet = Pet::factory()->create(['user_id' => $owner->id]);

        // Reference point: -22.9068, -43.1729 (Rio de Janeiro)
        // Near report: ~0km away
        $nearReport = $this->createReportWithLocation(
            ['pet_id' => $nearPet->id, 'user_id' => $owner->id],
            lng: -43.1729, lat: -22.9068
        );

        // Far report: ~50km away (Niterói area)
        $farReport = $this->createReportWithLocation(
            ['pet_id' => $farPet->id, 'user_id' => $owner->id],
            lng: -43.1000, lat: -22.5000
        );

        $response = $this->getJson('/api/v1/pet-reports?latitude=-22.9068&longitude=-43.1729&radius_km=100&paginate=false');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        // Nearest first
        $response->assertJsonPath('data.0.id', $nearReport->id);
        $response->assertJsonPath('data.1.id', $farReport->id);
    }

    // ──────────────────────────────────────────────
    // VALIDATION — invalid values
    // ──────────────────────────────────────────────

    public function test_index_validates_invalid_species(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pet-reports?species=BIRD');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['species']);
    }

    public function test_index_validates_invalid_size(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pet-reports?size=HUGE');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['size']);
    }

    public function test_index_validates_invalid_paginate(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pet-reports?paginate=no');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['paginate']);
    }
}
