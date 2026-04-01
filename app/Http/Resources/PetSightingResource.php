<?php

namespace App\Http\Resources;

use App\Models\PetSighting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetSighting
 */
class PetSightingResource extends JsonResource
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
            'userId' => $this->user_id,
            'title' => $this->title,
            'species' => $this->species?->value,
            'size' => $this->size?->value,
            'sex' => $this->sex?->value,
            'color' => $this->color,
            'breed' => new BreedResource($this->whenLoaded('breed')),
            'photos' => PetSightingPhotoResource::collection($this->whenLoaded('photos')),
            'characteristics' => CharacteristicResource::collection($this->whenLoaded('characteristics')),
            'location' => [
                'latitude' => $this->latitude ?? null,
                'longitude' => $this->longitude ?? null,
            ],
            'addressHint' => $this->address_hint,
            'description' => $this->description,
            'sightedAt' => $this->sighted_at,
            'sharePhone' => $this->share_phone,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatarUrl' => $this->user->avatar_url,
            ]),
            'distanceMeters' => $this->when(isset($this->resource->distance_meters), fn () => round((float) $this->resource->distance_meters, 2)),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
