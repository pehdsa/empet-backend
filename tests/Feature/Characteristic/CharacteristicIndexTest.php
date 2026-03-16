<?php

namespace Tests\Feature\Characteristic;

use App\Models\Characteristic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CharacteristicIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_active_characteristics(): void
    {
        Characteristic::factory()->count(3)->create(['is_active' => true]);
        Characteristic::factory()->count(2)->create(['is_active' => false]);

        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/characteristics');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_filters_by_valid_category(): void
    {
        Characteristic::factory()->count(2)->marking()->create();
        Characteristic::factory()->count(3)->coat()->create();

        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/characteristics?category=MARKING');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_invalid_category_returns_422(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/characteristics?category=INVALID');

        $response->assertStatus(422);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/characteristics');

        $response->assertStatus(401);
    }
}
