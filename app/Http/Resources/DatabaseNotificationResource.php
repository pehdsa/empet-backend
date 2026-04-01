<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @mixin DatabaseNotification
 */
class DatabaseNotificationResource extends JsonResource
{
    /** @var array<string, string> */
    private const TYPE_MAP = [
        'App\Notifications\PetLostNearby' => 'pet_lost_nearby',
        'App\Notifications\PetMatchesFound' => 'matches_found',
        'App\Notifications\PetReportSightingReported' => 'pet_report_sighting_reported',
        'App\Notifications\PetSightingReported' => 'pet_sighting_reported',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->resolveType($this->type),
            'data' => $this->data,
            'readAt' => $this->read_at,
            'createdAt' => $this->created_at,
        ];
    }

    /**
     * Resolve the notification type to a stable alias.
     */
    private function resolveType(string $fqcn): string
    {
        if (isset(self::TYPE_MAP[$fqcn])) {
            return self::TYPE_MAP[$fqcn];
        }

        Log::warning('DatabaseNotificationResource: unmapped notification type', [
            'type' => $fqcn,
        ]);

        return Str::snake(class_basename($fqcn));
    }
}
