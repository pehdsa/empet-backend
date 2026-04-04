<?php

namespace App\Services;

use App\Enums\PetSex;
use App\Models\Pet;
use App\Models\PetSighting;
use App\Support\Matching\MatchScoreResult;

class MatchScoringService
{
    public const MAX_RADIUS_METERS = 25000;

    public const SCORE_THRESHOLD = 30;

    public const PROXIMITY_MAX = 35;

    public const BREED_MAX = 25;

    public const SIZE_MAX = 10;

    public const SEX_MAX = 10;

    public const COLOR_MAX = 5;

    public const CHARACTERISTICS_MAX = 10;

    private const COLOR_STOPWORDS = ['e', 'com', 'de', 'o', 'a'];

    /**
     * Calculate matching score between a sighting and a lost pet.
     */
    public function calculateScore(PetSighting $sighting, Pet $lostPet, ?float $distance): MatchScoreResult
    {
        $proximity = $this->proximityScore($distance);
        $breed = $this->breedScore($sighting, $lostPet);
        $size = $this->sizeScore($sighting, $lostPet);
        $sex = $this->sexScore($sighting, $lostPet);
        $color = $this->colorScore($sighting, $lostPet);
        $characteristics = $this->characteristicsScore($sighting, $lostPet);

        return new MatchScoreResult(
            total: $proximity + $breed + $size + $sex + $color + $characteristics,
            proximity: $proximity,
            breed: $breed,
            size: $size,
            sex: $sex,
            color: $color,
            characteristics: $characteristics,
        );
    }

    /**
     * Proximity score: 0m = 35pts, max_radius = 0pts.
     */
    private function proximityScore(?float $distance): float
    {
        if ($distance === null) {
            return 0;
        }

        return max(0, self::PROXIMITY_MAX * (1 - $distance / self::MAX_RADIUS_METERS));
    }

    /**
     * Breed score based on sighting breed vs pet breeds.
     *
     * Both unknown = +5, one unknown = 0,
     * primary match = +25, secondary match = +12,
     * known mismatch = -15.
     */
    private function breedScore(PetSighting $sighting, Pet $lostPet): float
    {
        $sightingBreed = $sighting->breed_id;

        $lostBreeds = array_values(array_filter(
            [$lostPet->breed_id, $lostPet->secondary_breed_id ?? null],
            fn ($id) => $id !== null
        ));

        if ($sightingBreed === null && empty($lostBreeds)) {
            return 5;
        }

        if ($sightingBreed === null || empty($lostBreeds)) {
            return 0;
        }

        if ($lostPet->breed_id !== null && $sightingBreed === $lostPet->breed_id) {
            return 25;
        }

        if (in_array($sightingBreed, $lostBreeds)) {
            return 12;
        }

        return -15;
    }

    /**
     * Size score: exact = 10, 1 level diff = 4, 2+ levels = 0.
     */
    private function sizeScore(PetSighting $sighting, Pet $lostPet): float
    {
        $sizeOrder = ['SMALL' => 0, 'MEDIUM' => 1, 'LARGE' => 2];

        $sightingSize = $sighting->size ? ($sizeOrder[$sighting->size->value] ?? null) : null;
        $lostSize = $lostPet->size ? ($sizeOrder[$lostPet->size->value] ?? null) : null;

        if ($sightingSize === null || $lostSize === null) {
            return 0;
        }

        return match (abs($sightingSize - $lostSize)) {
            0 => 10,
            1 => 4,
            default => 0,
        };
    }

    /**
     * Sex score: both known and equal = +10, at least one unknown = +5,
     * both known and different = -10.
     */
    private function sexScore(PetSighting $sighting, Pet $lostPet): float
    {
        $sightingSex = $sighting->sex;
        $lostSex = $lostPet->sex;

        if ($sightingSex === null || $lostSex === null) {
            return 0;
        }

        if ($sightingSex === PetSex::Unknown || $lostSex === PetSex::Unknown) {
            return 5;
        }

        if ($sightingSex === $lostSex) {
            return 10;
        }

        return -10;
    }

    /**
     * Color score using Jaccard token intersection. Max 5pts.
     */
    private function colorScore(PetSighting $sighting, Pet $lostPet): float
    {
        $sightingColor = $sighting->color;
        $lostColor = $lostPet->primary_color;

        if ($sightingColor === null && $lostColor === null) {
            return 3;
        }

        if ($sightingColor === null || $lostColor === null) {
            return 0;
        }

        $sightingTokens = $this->colorTokens($sightingColor);
        $lostTokens = $this->colorTokens($lostColor);

        if (empty($sightingTokens) || empty($lostTokens)) {
            return 0;
        }

        $intersection = count(array_intersect($sightingTokens, $lostTokens));
        $union = count(array_unique(array_merge($sightingTokens, $lostTokens)));

        if ($union === 0) {
            return 0;
        }

        return ($intersection / $union) * self::COLOR_MAX;
    }

    /**
     * Normalize and tokenize a color string, discarding stopwords.
     *
     * @return array<int, string>
     */
    private function colorTokens(string $color): array
    {
        $normalized = mb_strtolower(trim($color));
        $tokens = preg_split('/[\s,\/]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(
            $tokens,
            fn ($token) => ! in_array($token, self::COLOR_STOPWORDS)
        ));
    }

    /**
     * Characteristics score: Jaccard index * 10. Both empty = 5.
     */
    private function characteristicsScore(PetSighting $sighting, Pet $lostPet): float
    {
        $sightingIds = $sighting->characteristics->pluck('id')->toArray();
        $lostIds = $lostPet->characteristics->pluck('id')->toArray();

        if (empty($sightingIds) && empty($lostIds)) {
            return 5;
        }

        $intersection = count(array_intersect($sightingIds, $lostIds));
        $union = count(array_unique(array_merge($sightingIds, $lostIds)));

        if ($union === 0) {
            return 5;
        }

        return ($intersection / $union) * self::CHARACTERISTICS_MAX;
    }
}
