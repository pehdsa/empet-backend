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
        $data = [
            'id' => $this->id,
            'reportId' => $this->report_id,
            'userId' => $this->user_id,
            'location' => [
                'latitude' => $this->latitude ?? null,
                'longitude' => $this->longitude ?? null,
            ],
            'addressHint' => $this->address_hint,
            'description' => $this->description,
            'sightedAt' => $this->sighted_at,
            'sharePhone' => $this->share_phone,
            'isActive' => $this->is_active,
            'user' => new UserResource($this->whenLoaded('user')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        $data = $this->appendContactPhone($data, $request);

        return $data;
    }

    /**
     * Append contactPhone only when share_phone is true and the requester is the report owner.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function appendContactPhone(array $data, Request $request): array
    {
        if (! $this->share_phone) {
            return $data;
        }

        $reportOwnerId = $this->report?->user_id;
        $authUserId = $request->user()?->id;

        if (! $reportOwnerId || ! $authUserId || $reportOwnerId !== $authUserId) {
            return $data;
        }

        $primaryPhone = $this->whenLoaded('user', function () {
            return $this->user->phones
                ->first(fn ($phone) => $phone->is_primary)
                ?->phone;
        });

        $data['contactPhone'] = $primaryPhone;

        return $data;
    }
}
