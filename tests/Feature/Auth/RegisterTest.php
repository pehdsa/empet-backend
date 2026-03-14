<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/auth/register';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson($this->endpoint, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role', 'avatarUrl', 'isActive', 'emailVerifiedAt', 'createdAt', 'updatedAt'],
                    'token',
                    'tokenType',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => UserRole::Client->value,
        ]);
    }

    public function test_register_returns_422_with_invalid_data(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_returns_422_with_duplicate_email_of_active_user(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson($this->endpoint, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_password_is_hashed_with_argon2id(): void
    {
        $this->postJson($this->endpoint, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'john@example.com')->first();

        $this->assertStringStartsWith('$argon2id$', $user->getRawOriginal('password'));
    }

    public function test_register_always_persists_client_role(): void
    {
        $response = $this->postJson($this->endpoint, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'ADMIN',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertEquals(UserRole::Client, $user->role);
    }

    public function test_register_restores_soft_deleted_user_with_same_email(): void
    {
        $deletedUser = User::factory()->trashed()->create([
            'email' => 'john@example.com',
            'name' => 'Old Name',
        ]);

        $response = $this->postJson($this->endpoint, [
            'name' => 'New Name',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertEquals($deletedUser->id, $user->id);
        $this->assertEquals('New Name', $user->name);
        $this->assertNull($user->deleted_at);
    }

    public function test_register_normalizes_email_to_lowercase(): void
    {
        $response = $this->postJson($this->endpoint, [
            'name' => 'John Doe',
            'email' => 'John@Example.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_register_trims_email_before_persisting(): void
    {
        $response = $this->postJson($this->endpoint, [
            'name' => 'John Doe',
            'email' => '  john@example.com  ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_register_restores_soft_deleted_user_resets_email_verified_at(): void
    {
        User::factory()->trashed()->create([
            'email' => 'john@example.com',
            'email_verified_at' => now(),
        ]);

        $this->postJson($this->endpoint, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNull($user->email_verified_at);
    }

    public function test_register_restore_preserves_unspecified_attributes(): void
    {
        User::factory()->trashed()->create([
            'email' => 'john@example.com',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ]);

        $this->postJson($this->endpoint, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertEquals('https://example.com/avatar.jpg', $user->avatar_url);
    }
}
