<?php

namespace Tests\Feature\PetSighting;

use App\Models\PetSighting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyPetSightingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_own_sightings(): void
    {
        $user = User::factory()->create();
        PetSighting::factory()->count(3)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pet-sightings/my');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'addressHint', 'sightedAt', 'createdAt'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_user_does_not_see_other_users_sightings(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        PetSighting::factory()->count(2)->create(['user_id' => $user->id]);
        PetSighting::factory()->count(3)->create(['user_id' => $other->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pet-sightings/my');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_returns_empty_when_no_sightings(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pet-sightings/my');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/pet-sightings/my');

        $response->assertUnauthorized();
    }
}
