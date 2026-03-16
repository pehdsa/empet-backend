<?php

namespace App\Actions\UserPhone;

use App\Models\UserPhone;

class DeleteUserPhone
{
    /**
     * Delete a phone and promote another to primary if needed.
     */
    public function handle(UserPhone $phone): void
    {
        $user = $phone->user;
        $wasPrimary = $phone->is_primary;

        $phone->delete();

        if ($wasPrimary) {
            $nextPhone = $user->phones()->orderBy('id')->first();
            $nextPhone?->update(['is_primary' => true]);
        }
    }
}
