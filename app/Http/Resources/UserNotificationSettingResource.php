<?php

namespace App\Http\Resources;

use App\Models\UserNotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserNotificationSetting
 */
class UserNotificationSettingResource extends JsonResource
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
            'notifyLostNearby' => $this->notify_lost_nearby,
            'notifyMatches' => $this->notify_matches,
            'notifySightings' => $this->notify_sightings,
            'nearbyRadiusKm' => $this->nearby_radius_km,
            'location' => $this->latitude !== null ? [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ] : null,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
