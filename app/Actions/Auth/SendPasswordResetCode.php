<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\ForgotPasswordData;
use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SendPasswordResetCode
{
    /**
     * Send a password reset code to the user's email.
     */
    public function handle(ForgotPasswordData $data): void
    {
        $user = User::query()
            ->where('email', $data->email)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return;
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_codes')->updateOrInsert(
            ['email' => $data->email],
            [
                'code_hash' => Hash::make($code),
                'code_expires_at' => now()->addMinutes(15),
                'reset_token_hash' => null,
                'token_expires_at' => null,
                'attempts' => 0,
                'verified_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $user->notify(new PasswordResetCodeNotification($code));
    }
}
