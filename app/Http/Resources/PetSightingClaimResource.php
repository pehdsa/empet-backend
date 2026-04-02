<?php

namespace App\Http\Resources;

use App\Models\PetSightingClaim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetSightingClaim
 */
class PetSightingClaimResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sighting = $this->sighting;
        $owner = $sighting->user;
        $ownerPhone = $sighting->share_phone
            ? $owner->phones->firstWhere('is_primary', true)
            : null;

        return [
            'sightingId' => $sighting->id,
            'sightingOwner' => [
                'name' => $owner->name,
                'phone' => $ownerPhone?->phone,
                'phoneIsWhatsapp' => $ownerPhone?->is_whatsapp,
            ],
        ];
    }
}
