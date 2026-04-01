<?php

namespace App\Http\Resources;

use App\Models\PetMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetMatch
 */
class PetMatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reportId' => $this->report_id,
            'sightingId' => $this->sighting_id,
            'score' => $this->score,
            'distanceMeters' => $this->distance_meters,
            'status' => $this->status->value,
            'sighting' => new PetSightingResource($this->whenLoaded('sighting')),
            'isSightingDeleted' => $this->whenLoaded('sighting', fn () => $this->sighting?->trashed() ?? false),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
