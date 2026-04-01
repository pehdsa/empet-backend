<?php

namespace App\Actions\PetSighting;

use App\Enums\PetMatchStatus;
use App\Models\PetSighting;
use Illuminate\Support\Facades\DB;

class DestroyPetSighting
{
    /**
     * Soft delete the sighting and purge PENDING matches.
     * DISMISSED and CONFIRMED matches are preserved for historical data.
     */
    public function handle(PetSighting $sighting): void
    {
        DB::transaction(function () use ($sighting): void {
            $sighting->matches()
                ->where('status', PetMatchStatus::Pending)
                ->delete();

            $sighting->delete();
        });
    }
}
