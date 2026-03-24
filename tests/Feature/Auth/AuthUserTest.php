<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthUserTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/auth/user';

    public function test_authenticated_user_can_get_current_user(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'avatarUrl',
                    'isActive',
                    'emailVerifiedAt',
                    'createdAt',
                    'updatedAt',
                ],
            ])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_unauthenticated_user_gets_401(): void
    {
        $response = $this->getJson($this->endpoint);

        $response->assertStatus(401);
    }
}
