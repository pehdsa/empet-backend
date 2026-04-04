<?php

namespace App\Support\Matching;

final readonly class MatchScoreResult
{
    public function __construct(
        public float $total,
        public float $proximity,
        public float $breed,
        public float $size,
        public float $sex,
        public float $color,
        public float $characteristics,
    ) {}
}
