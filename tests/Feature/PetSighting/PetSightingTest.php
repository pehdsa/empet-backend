<?php

namespace Tests\Feature\PetSighting;

use App\Models\Breed;
use App\Models\Characteristic;
use App\Models\PetSighting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetSightingTest extends TestCase
{
    use RefreshDatabase;

    private function sightingPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Dog spotted near the park',
            'latitude' => -23.5550,
            'longitude' => -46.6380,
            'sighted_at' => now()->subHour()->toIso8601String(),
            'species' => 'DOG',
        ], $overrides);
    }

    // ─── STORE ───────────────────────────────────────────────

    public function test_store_creates_sighting(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pet-sightings', $this->sightingPayload([
            'size' => 'MEDIUM',
            'sex' => 'MALE',
            'color' => 'marrom',
            'address_hint' => 'Near Ibirapuera Park',
            'description' => 'Dog without collar, friendly',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Dog spotted near the park')
            ->assertJsonPath('data.species', 'DOG')
            ->assertJsonPath('data.size', 'MEDIUM')
            ->assertJsonPath('data.sex', 'MALE')
            ->assertJsonPath('data.color', 'marrom')
            ->assertJsonPath('data.userId', $user->id);

        $this->assertDatabaseHas('pet_sightings', [
            'user_id' => $user->id,
            'title' => 'Dog spotted near the park',
            'species' => 'DOG',
        ]);
    }

    public function test_store_with_photos(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pet-sightings', $this->sightingPayload([
            'photos' => [
                UploadedFile::fake()->image('photo1.jpg', 800, 600),
                UploadedFile::fake()->image('photo2.jpg', 800, 600),
            ],
        ]));

        $response->assertCreated()
            ->assertJsonCount(2, 'data.photos');

        $this->assertDatabaseCount('pet_sighting_photos', 2);
    }

    public function test_store_with_characteristics(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $chars = Characteristic::factory()->count(2)->create();

        $response = $this->postJson('/api/v1/pet-sightings', $this->sightingPayload([
            'characteristic_ids' => $chars->pluck('id')->toArray(),
        ]));

        $response->assertCreated()
            ->assertJsonCount(2, 'data.characteristics');
    }

    public function test_store_with_breed(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $breed = Breed::factory()->create(['species' => 'DOG']);

        $response = $this->postJson('/api/v1/pet-sightings', $this->sightingPayload([
            'breed_id' => $breed->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.breed.id', $breed->id);
    }

    public function test_store_rejects_breed_from_wrong_species(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $catBreed = Breed::factory()->create(['species' => 'CAT']);

        $this->postJson('/api/v1/pet-sightings', $this->sightingPayload([
            'species' => 'DOG',
            'breed_id' => $catBreed->id,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['breed_id']);
    }

    public function test_store_accepts_unknown_sex(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pet-sightings', $this->sightingPayload([
            'sex' => 'UNKNOWN',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.sex', 'UNKNOWN');
    }

    public function test_store_rejects_more_than_3_photos(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/pet-sightings', $this->sightingPayload([
            'photos' => [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
                UploadedFile::fake()->image('3.jpg'),
                UploadedFile::fake()->image('4.jpg'),
            ],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['photos']);
    }

    public function test_store_requires_title(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $payload = $this->sightingPayload();
        unset($payload['title']);

        $this->postJson('/api/v1/pet-sightings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_requires_species(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $payload = $this->sightingPayload();
        unset($payload['species']);

        $this->postJson('/api/v1/pet-sightings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['species']);
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/pet-sightings', $this->sightingPayload())
            ->assertUnauthorized();
    }

    public function test_store_forbidden_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, ['*']);

        $this->postJson('/api/v1/pet-sightings', $this->sightingPayload())
            ->assertForbidden();
    }

    // ─── INDEX ───────────────────────────────────────────────

    public function test_index_returns_sightings_within_radius(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        PetSighting::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/pet-sightings?latitude=-23.55&longitude=-46.63&radius_km=50');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_index_requires_coordinates(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/pet-sightings')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_index_filters_by_species(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        PetSighting::factory()->dog()->count(2)->create();
        PetSighting::factory()->cat()->create();

        $response = $this->getJson('/api/v1/pet-sightings?latitude=-23.55&longitude=-46.63&radius_km=50&species=DOG');

        $response->assertOk();
        $species = collect($response->json('data'))->pluck('species')->unique()->values()->all();
        $this->assertEquals(['DOG'], $species);
    }

    public function test_index_rejects_radius_above_50(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/pet-sightings?latitude=-23.55&longitude=-46.63&radius_km=51')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius_km']);
    }

    public function test_index_rejects_radius_zero(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/pet-sightings?latitude=-23.55&longitude=-46.63&radius_km=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius_km']);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/pet-sightings?latitude=-23.55&longitude=-46.63')
            ->assertUnauthorized();
    }

    public function test_index_excludes_soft_deleted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        PetSighting::factory()->create();
        $deleted = PetSighting::factory()->create();
        $deleted->delete();

        $response = $this->getJson('/api/v1/pet-sightings?latitude=-23.55&longitude=-46.63&radius_km=50');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($deleted->id, $ids);
    }

    // ─── SHOW ───────────────────────────────────────────────

    public function test_show_returns_sighting_detail(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $sighting = PetSighting::factory()->create();

        $this->getJson("/api/v1/pet-sightings/{$sighting->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $sighting->id)
            ->assertJsonPath('data.title', $sighting->title);
    }

    public function test_show_does_not_expose_contact_phone(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $sighting = PetSighting::factory()->withSharePhone()->create();

        $response = $this->getJson("/api/v1/pet-sightings/{$sighting->id}");

        $response->assertOk();
        $this->assertArrayNotHasKey('contactPhone', $response->json('data'));
    }

    public function test_show_returns_user_summary_only(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $sighting = PetSighting::factory()->create();

        $response = $this->getJson("/api/v1/pet-sightings/{$sighting->id}");

        $response->assertOk();
        $userData = $response->json('data.user');
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('name', $userData);
        $this->assertArrayHasKey('avatarUrl', $userData);
        $this->assertArrayNotHasKey('email', $userData);
        $this->assertArrayNotHasKey('role', $userData);
    }

    public function test_show_returns_404_for_soft_deleted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $sighting = PetSighting::factory()->create();
        $sighting->delete();

        $this->getJson("/api/v1/pet-sightings/{$sighting->id}")
            ->assertNotFound();
    }

    public function test_show_requires_authentication(): void
    {
        $sighting = PetSighting::factory()->create();

        $this->getJson("/api/v1/pet-sightings/{$sighting->id}")
            ->assertUnauthorized();
    }

    // ─── DELETE ──────────────────────────────────────────────

    public function test_destroy_soft_deletes_sighting(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $sighting = PetSighting::factory()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/v1/pet-sightings/{$sighting->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Sighting deleted successfully.');

        $this->assertSoftDeleted('pet_sightings', ['id' => $sighting->id]);
    }

    public function test_destroy_allowed_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, ['*']);

        $sighting = PetSighting::factory()->create();

        $this->deleteJson("/api/v1/pet-sightings/{$sighting->id}")
            ->assertOk();

        $this->assertSoftDeleted('pet_sightings', ['id' => $sighting->id]);
    }

    public function test_destroy_forbidden_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($other, ['*']);

        $sighting = PetSighting::factory()->create(['user_id' => $owner->id]);

        $this->deleteJson("/api/v1/pet-sightings/{$sighting->id}")
            ->assertForbidden();
    }

    public function test_destroy_returns_404_for_already_deleted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $sighting = PetSighting::factory()->create(['user_id' => $user->id]);
        $sighting->delete();

        $this->deleteJson("/api/v1/pet-sightings/{$sighting->id}")
            ->assertNotFound();
    }

    public function test_destroy_requires_authentication(): void
    {
        $sighting = PetSighting::factory()->create();

        $this->deleteJson("/api/v1/pet-sightings/{$sighting->id}")
            ->assertUnauthorized();
    }
}
