<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/auth/logout';

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJson(['data' => ['message' => 'Logged out successfully.']]);

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_logout_returns_401_for_unauthenticated_user(): void
    {
        $response = $this->postJson($this->endpoint);

        $response->assertStatus(401);
    }

    public function test_logout_only_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('api');
        $otherToken = $user->createToken('other-device');

        $this->withHeader('Authorization', 'Bearer '.$currentToken->plainTextToken)
            ->postJson($this->endpoint);

        $this->assertEquals(1, $user->tokens()->count());
        $this->assertNotNull($user->tokens()->find($otherToken->accessToken->id));
    }
}
