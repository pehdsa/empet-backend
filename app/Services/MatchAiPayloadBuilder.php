<?php

namespace App\Services;

use App\Models\Pet;
use App\Models\PetSighting;
use App\Support\Matching\MatchAiInput;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MatchAiPayloadBuilder
{
    /**
     * Monta o MatchAiInput a partir dos models.
     */
    public function build(Pet $lostPet, PetSighting $sighting, float $distanceMeters): MatchAiInput
    {
        return new MatchAiInput(
            lostPet: $this->buildPetPayload($lostPet),
            sighting: $this->buildSightingPayload($sighting),
            lostPetPhotoUrls: $this->buildPhotoUrls($lostPet->photos),
            sightingPhotoUrls: $this->buildPhotoUrls($sighting->photos),
            distanceMeters: $distanceMeters,
            daysSinceSighting: (int) $sighting->sighted_at->diffInDays(now()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPetPayload(Pet $pet): array
    {
        return [
            'species' => $pet->species?->value,
            'breed' => $pet->breed?->name,
            'size' => $pet->size?->value,
            'sex' => $pet->sex?->value,
            'color' => $pet->primary_color,
            'characteristics' => $pet->characteristics->pluck('name')->values()->all(),
            'notes' => $this->truncate($pet->notes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSightingPayload(PetSighting $sighting): array
    {
        return [
            'species' => $sighting->species?->value,
            'breed' => $sighting->breed?->name,
            'size' => $sighting->size?->value,
            'sex' => $sighting->sex?->value,
            'color' => $sighting->color,
            'characteristics' => $sighting->characteristics->pluck('name')->values()->all(),
            'description' => $this->truncate($sighting->description),
        ];
    }

    /**
     * @param  Collection<int, Model>  $photos
     * @return array<int, string>
     */
    private function buildPhotoUrls(Collection $photos): array
    {
        $maxPhotos = (int) config('services.match_ai.max_photos', 3);

        if ($maxPhotos <= 0 || $photos->isEmpty()) {
            return [];
        }

        $disk = Storage::disk('s3');
        $expiresAt = now()->addMinutes(15);

        return $photos
            ->take($maxPhotos)
            ->map(function ($photo) use ($disk, $expiresAt): string {
                try {
                    return $disk->temporaryUrl($photo->path, $expiresAt);
                } catch (Throwable) {
                    return $disk->url($photo->path);
                }
            })
            ->values()
            ->all();
    }

    private function truncate(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return (string) Str::of($text)->squish()->limit(200, '');
    }
}
