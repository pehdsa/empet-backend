<?php

namespace App\Actions\UserPhone;

use App\DTOs\UserPhone\StoreUserPhoneData;
use App\Models\User;
use App\Models\UserPhone;

class StoreUserPhone
{
    /**
     * Create a new phone for the given user.
     */
    public function handle(StoreUserPhoneData $data, User $user): UserPhone
    {
        $isFirst = $user->phones()->count() === 0;
        $isPrimary = $isFirst || $data->isPrimary;

        if ($isPrimary) {
            $user->phones()->update(['is_primary' => false]);
        }

        return $user->phones()->create([
            'phone' => $data->phone,
            'is_whatsapp' => $data->isWhatsapp,
            'is_primary' => $isPrimary,
            'label' => $data->label,
        ]);
    }
}
