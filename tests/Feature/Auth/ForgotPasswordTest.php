<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private string $endpoint = '/api/v1/auth/forgot-password';

    public function test_forgot_password_returns_200_with_valid_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->postJson($this->endpoint, [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['message' => 'Se o e-mail estiver cadastrado, você receberá um código de recuperação.']]);
    }

    public function test_forgot_password_returns_200_with_nonexistent_email(): void
    {
        Notification::fake();

        $response = $this->postJson($this->endpoint, [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['message' => 'Se o e-mail estiver cadastrado, você receberá um código de recuperação.']]);
    }

    public function test_forgot_password_sends_notification_when_user_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson($this->endpoint, [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, PasswordResetCodeNotification::class);
    }

    public function test_forgot_password_does_not_send_notification_when_user_does_not_exist(): void
    {
        Notification::fake();

        $this->postJson($this->endpoint, [
            'email' => 'nonexistent@example.com',
        ]);

        Notification::assertNothingSent();
    }

    public function test_forgot_password_creates_reset_code_record(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson($this->endpoint, [
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('password_reset_codes', [
            'email' => $user->email,
            'attempts' => 0,
            'verified_at' => null,
        ]);
    }

    public function test_forgot_password_replaces_existing_record(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        DB::table('password_reset_codes')->insert([
            'email' => $user->email,
            'code_hash' => Hash::make('123456'),
            'code_expires_at' => now()->addMinutes(15),
            'attempts' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson($this->endpoint, [
            'email' => $user->email,
        ]);

        $this->assertDatabaseCount('password_reset_codes', 1);
        $this->assertDatabaseHas('password_reset_codes', [
            'email' => $user->email,
            'attempts' => 0,
        ]);
    }

    public function test_forgot_password_invalidates_previous_reset_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        DB::table('password_reset_codes')->insert([
            'email' => $user->email,
            'code_hash' => null,
            'code_expires_at' => null,
            'reset_token_hash' => Hash::make('old-token'),
            'token_expires_at' => now()->addMinutes(15),
            'verified_at' => now(),
            'attempts' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson($this->endpoint, [
            'email' => $user->email,
        ]);

        $record = DB::table('password_reset_codes')
            ->where('email', $user->email)
            ->first();

        $this->assertNull($record->reset_token_hash);
        $this->assertNull($record->token_expires_at);
        $this->assertNull($record->verified_at);
        $this->assertEquals(0, $record->attempts);
    }

    public function test_forgot_password_returns_422_with_invalid_email_format(): void
    {
        $response = $this->postJson($this->endpoint, [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_returns_422_with_missing_email(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_normalizes_email_to_lowercase(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->postJson($this->endpoint, [
            'email' => 'USER@EXAMPLE.COM',
        ]);

        Notification::assertSentTo($user, PasswordResetCodeNotification::class);

        $this->assertDatabaseHas('password_reset_codes', [
            'email' => 'user@example.com',
        ]);
    }

    public function test_forgot_password_does_not_send_notification_for_soft_deleted_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->delete();

        $this->postJson($this->endpoint, [
            'email' => $user->email,
        ]);

        Notification::assertNotSentTo($user, PasswordResetCodeNotification::class);
    }

    public function test_forgot_password_does_not_send_notification_for_inactive_user(): void
    {
        Notification::fake();
        $user = User::factory()->create(['is_active' => false]);

        $this->postJson($this->endpoint, [
            'email' => $user->email,
        ]);

        Notification::assertNotSentTo($user, PasswordResetCodeNotification::class);
    }
}
