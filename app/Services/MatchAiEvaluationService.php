<?php

namespace App\Services;

use App\Contracts\MatchAiProvider;
use App\Models\Pet;
use App\Models\PetSighting;
use App\Support\Matching\MatchAiResult;
use Illuminate\Support\Str;
use Throwable;

class MatchAiEvaluationService
{
    public function __construct(
        private readonly MatchAiProvider $provider,
        private readonly MatchAiPayloadBuilder $payloadBuilder,
    ) {}

    /**
     * Avalia um match via IA e retorna resultado normalizado.
     */
    public function evaluate(Pet $lostPet, PetSighting $sighting, float $distanceMeters): MatchAiResult
    {
        try {
            $input = $this->payloadBuilder->build($lostPet, $sighting, $distanceMeters);
            $result = $this->provider->evaluate($input);

            if ($result->success && ($result->score === null || $result->score < 0 || $result->score > 100)) {
                return MatchAiResult::failed($result->provider, $result->model);
            }

            if ($result->success && $result->confidence !== null && ($result->confidence < 0 || $result->confidence > 1)) {
                return MatchAiResult::failed($result->provider, $result->model);
            }

            return new MatchAiResult(
                success: $result->success,
                score: $result->score,
                confidence: $result->confidence,
                summary: $result->summary !== null ? (string) Str::of($result->summary)->squish()->limit(255, '') : null,
                provider: $result->provider,
                model: $result->model,
                rawResponse: $result->rawResponse,
            );
        } catch (Throwable) {
            return MatchAiResult::failed(
                config('services.match_ai.provider', 'unknown'),
                config('services.match_ai.openai.model', 'unknown'),
            );
        }
    }

    /**
     * Calcula o ajuste no score baseado no ai_score.
     *
     * Guarda de baixa confianca: se confidence < 0.4, nao aplica ajuste.
     * Evita boost alto em resultados pouco confiaveis.
     */
    public static function calculateAdjustment(?float $aiScore, ?float $confidence = null): int
    {
        if ($aiScore === null) {
            return 0;
        }

        if ($confidence !== null && $confidence < 0.4) {
            return 0;
        }

        return match (true) {
            $aiScore >= 80 => 10,
            $aiScore >= 60 => 5,
            $aiScore >= 40 => 0,
            default => -10,
        };
    }

    /**
     * Calcula final_score com clamp para garantir faixa 0-100.
     */
    public static function calculateFinalScore(float $baseScore, ?float $aiScore, ?float $confidence = null): float
    {
        $adjustment = self::calculateAdjustment($aiScore, $confidence);

        return round(max(0, min(100, $baseScore + $adjustment)), 2);
    }
}
