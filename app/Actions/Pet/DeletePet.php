<?php

namespace App\Actions\Pet;

use App\Models\Pet;

class DeletePet
{
    /**
     * Soft delete a pet. Photos are kept in storage.
     */
    public function handle(Pet $pet): void
    {
        $pet->delete();
    }
}
