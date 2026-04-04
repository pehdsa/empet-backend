<?php

namespace App\Support\Matching;

final readonly class MatchAiResult
{
    public function __construct(
        public bool $success,
        public ?float $score,
        public ?float $confidence,
        public ?string $summary,
        public string $provider,
        public string $model,
        public ?string $rawResponse = null,
    ) {}

    /**
     * Cria resultado de falha.
     */
    public static function failed(string $provider, string $model): self
    {
        return new self(
            success: false,
            score: null,
            confidence: null,
            summary: null,
            provider: $provider,
            model: $model,
        );
    }
}
