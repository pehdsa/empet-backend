<?php

namespace Tests\Feature\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_admin_user(): void
    {
        $this->artisan('app:create-admin-user', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'admin-password123',
        ])
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(UserRole::Admin, $user->role);
        $this->assertEquals('Admin User', $user->name);
    }

    public function test_command_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('app:create-admin-user', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'admin-password123',
        ])
            ->assertFailed();
    }

    public function test_command_fails_for_soft_deleted_email(): void
    {
        User::factory()->trashed()->create(['email' => 'admin@example.com']);

        $this->artisan('app:create-admin-user', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'admin-password123',
        ])
            ->assertFailed();
    }

    public function test_command_requires_force_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('app:create-admin-user', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'admin-password123',
        ])
            ->assertFailed();
    }
}
