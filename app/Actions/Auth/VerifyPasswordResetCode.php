<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\VerifyResetCodeData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VerifyPasswordResetCode
{
    /**
     * Verify the password reset code and return a reset token.
     */
    public function handle(VerifyResetCodeData $data): string
    {
        $record = DB::table('password_reset_codes')
            ->where('email', $data->email)
            ->first();

        if (! $record) {
            throw $this->validationException();
        }

        if ($record->verified_at !== null) {
            throw $this->validationException();
        }

        if ($record->attempts >= 5) {
            DB::table('password_reset_codes')
                ->where('email', $data->email)
                ->delete();

            throw $this->validationException();
        }

        if ($record->code_expires_at < now()) {
            throw $this->validationException();
        }

        if (! Hash::check($data->code, $record->code_hash)) {
            DB::table('password_reset_codes')
                ->where('email', $data->email)
                ->increment('attempts', 1, ['updated_at' => now()]);

            throw $this->validationException();
        }

        $resetToken = Str::random(64);

        DB::transaction(function () use ($data, $resetToken) {
            DB::table('password_reset_codes')
                ->where('email', $data->email)
                ->lockForUpdate()
                ->first();

            DB::table('password_reset_codes')
                ->where('email', $data->email)
                ->update([
                    'code_hash' => null,
                    'code_expires_at' => null,
                    'reset_token_hash' => Hash::make($resetToken),
                    'token_expires_at' => now()->addMinutes(15),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        return $resetToken;
    }

    /**
     * @throws ValidationException
     */
    private function validationException(): ValidationException
    {
        return ValidationException::withMessages([
            'code' => ['Código inválido ou expirado.'],
        ]);
    }
}
