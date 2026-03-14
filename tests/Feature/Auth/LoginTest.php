<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/auth/login';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson($this->endpoint, [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                    'tokenType',
                ],
            ]);
    }

    public function test_login_returns_422_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson($this->endpoint, [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_returns_422_with_nonexistent_email(): void
    {
        $response = $this->postJson($this->endpoint, [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_for_soft_deleted_user(): void
    {
        User::factory()->trashed()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson($this->endpoint, [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_returns_422_for_inactive_user(): void
    {
        User::factory()->inactive()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson($this->endpoint, [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_returns_422_with_missing_fields(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_normalizes_email_to_lowercase(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson($this->endpoint, [
            'email' => 'John@Example.COM',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }

    public function test_login_trims_email_before_authentication(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson($this->endpoint, [
            'email' => '  john@example.com  ',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }

    public function test_login_revokes_previous_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $user->createToken('api');
        $user->createToken('api');
        $this->assertEquals(2, $user->tokens()->count());

        $this->postJson($this->endpoint, [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->assertEquals(1, $user->tokens()->count());
    }
}
