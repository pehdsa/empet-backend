<?php

namespace App\Http\Resources;

use App\Models\PetPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin PetPhoto
 */
class PetPhotoResource extends JsonResource
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
            'url' => Storage::disk('s3')->url($this->path),
            'position' => $this->position,
        ];
    }
}
