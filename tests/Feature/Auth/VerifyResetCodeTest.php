<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerifyResetCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private string $verifyEndpoint = '/api/v1/auth/verify-reset-code';

    private string $forgotEndpoint = '/api/v1/auth/forgot-password';

    private function createResetCode(string $email, string $code = '123456', array $overrides = []): void
    {
        $defaults = [
            'email' => $email,
            'code_hash' => Hash::make($code),
            'code_expires_at' => now()->addMinutes(15),
            'reset_token_hash' => null,
            'token_expires_at' => null,
            'attempts' => 0,
            'verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('password_reset_codes')->insert(array_merge($defaults, $overrides));
    }

    public function test_valid_code_returns_reset_token(): void
    {
        $user = User::factory()->create();
        $this->createResetCode($user->email);

        $response = $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['resetToken']);

        $this->assertNotEmpty($response->json('resetToken'));
    }

    public function test_invalid_code_returns_422(): void
    {
        $user = User::factory()->create();
        $this->createResetCode($user->email);

        $response = $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '999999',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_invalid_code_increments_attempts(): void
    {
        $user = User::factory()->create();
        $this->createResetCode($user->email);

        $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '999999',
        ]);

        $this->assertDatabaseHas('password_reset_codes', [
            'email' => $user->email,
            'attempts' => 1,
        ]);
    }

    public function test_valid_code_succeeds_after_failed_attempts(): void
    {
        $user = User::factory()->create();
        $this->createResetCode($user->email);

        $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '999999',
        ]);

        $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '888888',
        ]);

        $response = $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['resetToken']);
    }

    public function test_code_invalidated_after_5_failed_attempts(): void
    {
        $user = User::factory()->create();
        $this->createResetCode($user->email, '123456', ['attempts' => 4]);

        $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '999999',
        ]);

        $this->assertDatabaseHas('password_reset_codes', [
            'email' => $user->email,
            'attempts' => 5,
        ]);

        $response = $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('password_reset_codes', [
            'email' => $user->email,
        ]);
    }

    public function test_expired_code_returns_422(): void
    {
        $user = User::factory()->create();
        $this->createResetCode($user->email, '123456', [
            'code_expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_nonexistent_email_returns_422(): void
    {
        $response = $this->postJson($this->verifyEndpoint, [
            'email' => 'nonexistent@example.com',
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_already_verified_code_returns_422(): void
    {
        $user = User::factory()->create();
        $this->createResetCode($user->email, '123456', [
            'code_hash' => null,
            'code_expires_at' => null,
            'reset_token_hash' => Hash::make('some-token'),
            'token_expires_at' => now()->addMinutes(15),
            'verified_at' => now(),
        ]);

        $response = $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_returns_422_with_missing_fields(): void
    {
        $response = $this->postJson($this->verifyEndpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'code']);
    }

    public function test_returns_422_with_invalid_email_format(): void
    {
        $response = $this->postJson($this->verifyEndpoint, [
            'email' => 'not-an-email',
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_code_must_be_exactly_6_numeric_digits(): void
    {
        $user = User::factory()->create();
        $this->createResetCode($user->email);

        $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => 'abcdef',
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);

        $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '12345',
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);

        $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '1234567',
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);

        $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '12ab56',
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    public function test_new_forgot_password_request_invalidates_previously_verified_code(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->createResetCode($user->email);

        $response = $this->postJson($this->verifyEndpoint, [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $resetToken = $response->json('resetToken');
        $this->assertNotEmpty($resetToken);

        $this->postJson($this->forgotEndpoint, [
            'email' => $user->email,
        ]);

        $record = DB::table('password_reset_codes')
            ->where('email', $user->email)
            ->first();

        $this->assertNull($record->reset_token_hash);
        $this->assertNull($record->verified_at);
        $this->assertEquals(0, $record->attempts);
    }

    public function test_new_forgot_password_resets_attempts_counter(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->createResetCode($user->email, '123456', ['attempts' => 3]);

        $this->postJson($this->forgotEndpoint, [
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('password_reset_codes', [
            'email' => $user->email,
            'attempts' => 0,
        ]);
    }
}
