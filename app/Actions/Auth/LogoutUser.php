<?php

namespace App\Actions\Auth;

use App\Models\User;

class LogoutUser
{
    /**
     * Revoke the user's current access token.
     */
    public function handle(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
