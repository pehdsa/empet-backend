<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/auth/password';

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson($this->endpoint, [
            'current_password' => 'old-password123',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['message' => 'Password changed successfully.']]);

        $user->refresh();
        $this->assertTrue(Hash::check('new-password123', $user->password));
    }

    public function test_change_password_returns_401_for_unauthenticated_user(): void
    {
        $response = $this->putJson($this->endpoint, [
            'current_password' => 'old-password123',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_change_password_returns_422_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson($this->endpoint, [
            'current_password' => 'wrong-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_change_password_returns_422_when_new_password_same_as_current(): void
    {
        $user = User::factory()->create(['password' => 'same-password123']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson($this->endpoint, [
            'current_password' => 'same-password123',
            'password' => 'same-password123',
            'password_confirmation' => 'same-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_change_password_returns_422_when_confirmation_does_not_match(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson($this->endpoint, [
            'current_password' => 'old-password123',
            'password' => 'new-password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_change_password_returns_422_with_missing_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password', 'password']);
    }

    public function test_change_password_revokes_other_tokens(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);

        $keepToken = $user->createToken('api');
        $user->createToken('other-device');
        $user->createToken('another-device');
        $this->assertEquals(3, $user->tokens()->count());

        $this->withHeader('Authorization', 'Bearer '.$keepToken->plainTextToken)
            ->putJson($this->endpoint, [
                'current_password' => 'old-password123',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ]);

        $this->assertEquals(1, $user->tokens()->count());
        $this->assertNotNull($user->tokens()->find($keepToken->accessToken->id));
    }
}
