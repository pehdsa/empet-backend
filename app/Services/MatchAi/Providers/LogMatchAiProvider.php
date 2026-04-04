<?php

namespace App\Services\MatchAi\Providers;

use App\Contracts\MatchAiProvider;
use App\Support\Matching\MatchAiInput;
use App\Support\Matching\MatchAiResult;
use Illuminate\Support\Facades\Log;

class LogMatchAiProvider implements MatchAiProvider
{
    public function evaluate(MatchAiInput $input): MatchAiResult
    {
        $fixedScore = (float) config('services.match_ai.log.fixed_score', 70.0);
        $fixedConfidence = (float) config('services.match_ai.log.fixed_confidence', 0.8);

        Log::info('match_ai_log_provider_called', [
            'lost_pet' => $input->lostPet,
            'sighting' => $input->sighting,
            'lost_pet_photo_count' => count($input->lostPetPhotoUrls),
            'sighting_photo_count' => count($input->sightingPhotoUrls),
            'distance_meters' => $input->distanceMeters,
            'days_since_sighting' => $input->daysSinceSighting,
            'fixed_score' => $fixedScore,
            'fixed_confidence' => $fixedConfidence,
        ]);

        return new MatchAiResult(
            success: true,
            score: $fixedScore,
            confidence: $fixedConfidence,
            summary: 'LogMatchAiProvider: retorno fixo para desenvolvimento',
            provider: 'log',
            model: 'log',
        );
    }
}
