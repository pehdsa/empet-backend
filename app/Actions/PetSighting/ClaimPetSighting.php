<?php

namespace App\Actions\PetSighting;

use App\Models\PetSighting;
use App\Models\PetSightingClaim;
use App\Models\User;
use App\Notifications\PetSightingClaimed;
use Illuminate\Support\Facades\DB;

class ClaimPetSighting
{
    /**
     * Claim a pet sighting. Notifies the sighting owner only on the first claim.
     */
    public function handle(PetSighting $sighting, User $claimer): PetSightingClaim
    {
        $claim = PetSightingClaim::firstOrCreate([
            'pet_sighting_id' => $sighting->id,
            'user_id' => $claimer->id,
        ]);

        if ($claim->wasRecentlyCreated) {
            DB::afterCommit(function () use ($sighting, $claimer) {
                $sighting->user?->notify(new PetSightingClaimed($sighting, $claimer));
            });
        }

        return $claim;
    }
}
