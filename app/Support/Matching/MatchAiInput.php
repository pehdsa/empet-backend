<?php

namespace App\Support\Matching;

final readonly class MatchAiInput
{
    /**
     * @param  array<string, mixed>  $lostPet  Payload curado do pet perdido
     * @param  array<string, mixed>  $sighting  Payload curado do avistamento
     * @param  array<int, string>  $lostPetPhotoUrls  Ate N URLs (S3), limite configurável
     * @param  array<int, string>  $sightingPhotoUrls  Ate N URLs (S3), limite configurável
     */
    public function __construct(
        public array $lostPet,
        public array $sighting,
        public array $lostPetPhotoUrls,
        public array $sightingPhotoUrls,
        public float $distanceMeters,
        public int $daysSinceSighting,
    ) {}
}
