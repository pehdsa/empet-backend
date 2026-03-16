<?php

namespace Tests\Feature\Breed;

use App\Models\Breed;
use App\Models\User;
use Database\Seeders\BreedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BreedIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_active_breeds(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        Breed::factory()->dog()->create(['name' => 'Labrador']);
        Breed::factory()->dog()->create(['name' => 'Poodle']);
        Breed::factory()->dog()->inactive()->create(['name' => 'Hidden']);

        $response = $this->getJson('/api/v1/breeds');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_does_not_return_inactive_breeds(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        Breed::factory()->dog()->inactive()->create();
        Breed::factory()->cat()->inactive()->create();

        $response = $this->getJson('/api/v1/breeds');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_filters_by_species_dog(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        Breed::factory()->dog()->create(['name' => 'Labrador']);
        Breed::factory()->cat()->create(['name' => 'Siamês']);

        $response = $this->getJson('/api/v1/breeds?species=DOG');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.species', 'DOG');
    }

    public function test_filters_by_species_cat(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        Breed::factory()->dog()->create(['name' => 'Labrador']);
        Breed::factory()->cat()->create(['name' => 'Siamês']);

        $response = $this->getJson('/api/v1/breeds?species=CAT');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.species', 'CAT');
    }

    public function test_invalid_species_returns_422(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/breeds?species=BIRD');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['species']);
    }

    public function test_returns_ordered_by_name(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        Breed::factory()->dog()->create(['name' => 'Rottweiler']);
        Breed::factory()->dog()->create(['name' => 'Akita']);
        Breed::factory()->dog()->create(['name' => 'Labrador']);

        $response = $this->getJson('/api/v1/breeds');

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Akita', 'Labrador', 'Rottweiler'], $names);
    }

    public function test_srd_appears_for_both_dog_and_cat(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $this->seed(BreedSeeder::class);

        $dogResponse = $this->getJson('/api/v1/breeds?species=DOG');
        $dogNames = collect($dogResponse->json('data'))->pluck('name')->toArray();
        $this->assertContains('SRD (Sem Raça Definida)', $dogNames);

        $catResponse = $this->getJson('/api/v1/breeds?species=CAT');
        $catNames = collect($catResponse->json('data'))->pluck('name')->toArray();
        $this->assertContains('SRD (Sem Raça Definida)', $catNames);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/breeds');

        $response->assertStatus(401);
    }
}
