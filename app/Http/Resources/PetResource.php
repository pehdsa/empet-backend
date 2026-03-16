<?php

namespace App\Http\Resources;

use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pet
 */
class PetResource extends JsonResource
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
            'name' => $this->name,
            'species' => $this->species->value,
            'size' => $this->size->value,
            'sex' => $this->sex->value,
            'breed' => $this->breed,
            'secondaryBreed' => $this->secondary_breed,
            'primaryColor' => $this->primary_color,
            'notes' => $this->notes,
            'isActive' => $this->is_active,
            'photos' => PetPhotoResource::collection($this->whenLoaded('photos')),
            'characteristics' => CharacteristicResource::collection($this->whenLoaded('characteristics')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
