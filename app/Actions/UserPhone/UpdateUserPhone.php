<?php

namespace App\Actions\UserPhone;

use App\DTOs\UserPhone\UpdateUserPhoneData;
use App\Models\UserPhone;

class UpdateUserPhone
{
    /**
     * Update an existing phone.
     */
    public function handle(UpdateUserPhoneData $data): UserPhone
    {
        $phone = $data->userPhone;
        $user = $phone->user;

        if ($data->isPrimary) {
            $user->phones()->where('id', '!=', $phone->id)->update(['is_primary' => false]);
        }

        $phone->update([
            'phone' => $data->phone,
            'is_whatsapp' => $data->isWhatsapp,
            'is_primary' => $data->isPrimary,
            'label' => $data->label,
        ]);

        if (! $data->isPrimary && $phone->wasChanged('is_primary')) {
            $this->ensurePrimaryExists($user, $phone);
        }

        return $phone->refresh();
    }

    /**
     * Promote another phone to primary if needed.
     */
    private function ensurePrimaryExists($user, UserPhone $excludePhone): void
    {
        $hasPrimary = $user->phones()
            ->where('id', '!=', $excludePhone->id)
            ->where('is_primary', true)
            ->exists();

        if (! $hasPrimary) {
            $nextPhone = $user->phones()
                ->where('id', '!=', $excludePhone->id)
                ->orderBy('id')
                ->first();

            $nextPhone?->update(['is_primary' => true]);
        }
    }
}
