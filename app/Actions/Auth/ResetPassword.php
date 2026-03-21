<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\ResetPasswordData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPassword
{
    /**
     * Reset the user's password using a verified reset token.
     */
    public function handle(ResetPasswordData $data): void
    {
        $record = DB::table('password_reset_codes')
            ->where('email', $data->email)
            ->first();

        if (! $record) {
            throw $this->validationException();
        }

        if ($record->verified_at === null) {
            throw $this->validationException();
        }

        if ($record->token_expires_at < now()) {
            throw $this->validationException();
        }

        if (! Hash::check($data->resetToken, $record->reset_token_hash)) {
            throw $this->validationException();
        }

        $user = User::query()
            ->where('email', $data->email)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            DB::table('password_reset_codes')
                ->where('email', $data->email)
                ->delete();

            throw $this->validationException();
        }

        DB::transaction(function () use ($data, $user) {
            $user->update(['password' => $data->password]);

            DB::table('password_reset_codes')
                ->where('email', $data->email)
                ->delete();

            $user->tokens()->delete();
        });
    }

    /**
     * @throws ValidationException
     */
    private function validationException(): ValidationException
    {
        return ValidationException::withMessages([
            'resetToken' => ['Token inválido ou expirado.'],
        ]);
    }
}
