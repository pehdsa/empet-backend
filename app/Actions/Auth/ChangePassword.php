<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\ChangePasswordData;

class ChangePassword
{
    /**
     * Change the user's password and revoke other tokens.
     */
    public function handle(ChangePasswordData $data): void
    {
        $data->user->update([
            'password' => $data->password,
        ]);

        $currentTokenId = $data->user->currentAccessToken()->id;

        $data->user->tokens()
            ->where('id', '!=', $currentTokenId)
            ->delete();
    }
}
