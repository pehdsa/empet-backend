<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private string $resetEndpoint = '/api/v1/auth/reset-password';

    private function createVerifiedResetRecord(string $email, string $resetToken = 'valid-reset-token', array $overrides = []): void
    {
        $defaults = [
            'email' => $email,
            'code_hash' => null,
            'code_expires_at' => null,
            'reset_token_hash' => Hash::make($resetToken),
            'token_expires_at' => now()->addMinutes(15),
            'attempts' => 0,
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('password_reset_codes')->insert(array_merge($defaults, $overrides));
    }

    public function test_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create();
        $this->createVerifiedResetRecord($user->email);

        $response = $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'valid-reset-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['message' => 'Senha redefinida com sucesso.']]);

        $user->refresh();
        $this->assertTrue(Hash::check('new-password123', $user->password));
    }

    public function test_reset_password_revokes_all_sanctum_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('device-1');
        $user->createToken('device-2');
        $this->assertEquals(2, $user->tokens()->count());

        $this->createVerifiedResetRecord($user->email);

        $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'valid-reset-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_invalid_token_returns_422(): void
    {
        $user = User::factory()->create();
        $this->createVerifiedResetRecord($user->email);

        $response = $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'wrong-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resetToken']);
    }

    public function test_expired_token_returns_422(): void
    {
        $user = User::factory()->create();
        $this->createVerifiedResetRecord($user->email, 'valid-reset-token', [
            'token_expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'valid-reset-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resetToken']);
    }

    public function test_nonexistent_email_returns_422(): void
    {
        $response = $this->postJson($this->resetEndpoint, [
            'email' => 'nonexistent@example.com',
            'resetToken' => 'some-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resetToken']);
    }

    public function test_unverified_record_returns_422(): void
    {
        $user = User::factory()->create();

        DB::table('password_reset_codes')->insert([
            'email' => $user->email,
            'code_hash' => Hash::make('123456'),
            'code_expires_at' => now()->addMinutes(15),
            'reset_token_hash' => null,
            'token_expires_at' => null,
            'attempts' => 0,
            'verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'some-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resetToken']);
    }

    public function test_returns_422_with_missing_fields(): void
    {
        $response = $this->postJson($this->resetEndpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'resetToken', 'password']);
    }

    public function test_returns_422_when_password_confirmation_does_not_match(): void
    {
        $user = User::factory()->create();
        $this->createVerifiedResetRecord($user->email);

        $response = $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'valid-reset-token',
            'password' => 'new-password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_returns_422_with_weak_password(): void
    {
        $user = User::factory()->create();
        $this->createVerifiedResetRecord($user->email);

        $response = $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'valid-reset-token',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_token_is_deleted_after_successful_reset(): void
    {
        $user = User::factory()->create();
        $this->createVerifiedResetRecord($user->email);

        $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'valid-reset-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $this->assertDatabaseMissing('password_reset_codes', [
            'email' => $user->email,
        ]);

        $response = $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'valid-reset-token',
            'password' => 'another-password123',
            'password_confirmation' => 'another-password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_login_with_new_password_after_reset(): void
    {
        $user = User::factory()->create();
        $this->createVerifiedResetRecord($user->email);

        $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'valid-reset-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'new-password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_reset_password_fails_if_user_becomes_inactive_before_reset(): void
    {
        $user = User::factory()->create();
        $this->createVerifiedResetRecord($user->email);

        $user->update(['is_active' => false]);

        $response = $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'valid-reset-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resetToken']);

        $this->assertDatabaseMissing('password_reset_codes', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_fails_if_user_is_soft_deleted_before_reset(): void
    {
        $user = User::factory()->create();
        $this->createVerifiedResetRecord($user->email);

        $user->delete();

        $response = $this->postJson($this->resetEndpoint, [
            'email' => $user->email,
            'resetToken' => 'valid-reset-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resetToken']);

        $this->assertDatabaseMissing('password_reset_codes', [
            'email' => $user->email,
        ]);
    }
}
