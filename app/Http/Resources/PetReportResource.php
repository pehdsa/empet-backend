<?php

namespace App\Http\Resources;

use App\Models\PetReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetReport
 */
class PetReportResource extends JsonResource
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
            'petId' => $this->pet_id,
            'userId' => $this->user_id,
            'status' => $this->status->value,
            'location' => [
                'latitude' => $this->latitude ?? null,
                'longitude' => $this->longitude ?? null,
            ],
            'addressHint' => $this->address_hint,
            'description' => $this->description,
            'lostAt' => $this->lost_at,
            'foundAt' => $this->found_at,
            'isActive' => $this->is_active,
            'pet' => new PetResource($this->whenLoaded('pet')),
            'user' => new UserResource($this->whenLoaded('user')),
            'matches' => PetMatchResource::collection($this->whenLoaded('matches')),
            'matchesCount' => $this->whenCounted('matches'),
            'sightingsCount' => $this->whenCounted('sightings'),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
