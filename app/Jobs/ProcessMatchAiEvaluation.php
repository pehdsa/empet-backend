<?php

namespace App\Jobs;

use App\Enums\PetMatchStatus;
use App\Models\PetMatch;
use App\Services\MatchAiEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMatchAiEvaluation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    public $tries = 2;

    /**
     * @var array<int, int>
     */
    public $backoff = [30];

    /**
     * @var int
     */
    public $timeout = 30;

    public function __construct(
        private readonly PetMatch $match,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MatchAiEvaluationService $aiEvaluation): void
    {
        $match = $this->match->load([
            'report.pet.photos',
            'report.pet.breed',
            'report.pet.characteristics',
            'sighting.photos',
            'sighting.breed',
            'sighting.characteristics',
        ]);

        if ($match->status !== PetMatchStatus::Pending) {
            Log::info('match_ai_skipped', ['match_id' => $match->id, 'reason' => 'status_not_pending']);

            return;
        }

        if ($match->ai_status !== null) {
            Log::info('match_ai_skipped', ['match_id' => $match->id, 'reason' => 'already_evaluated']);

            return;
        }

        $lostPet = $match->report?->pet;
        if (! $lostPet) {
            Log::info('match_ai_skipped', ['match_id' => $match->id, 'reason' => 'no_pet']);

            return;
        }

        $startedAt = microtime(true);

        Log::info('match_ai_requested', [
            'match_id' => $match->id,
            'report_id' => $match->report_id,
            'sighting_id' => $match->sighting_id,
            'base_score' => (float) $match->base_score,
            'has_photos' => $lostPet->photos->isNotEmpty() || $match->sighting->photos->isNotEmpty(),
        ]);

        $result = $aiEvaluation->evaluate($lostPet, $match->sighting, (float) $match->distance_meters);

        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($result->success) {
            $finalScore = MatchAiEvaluationService::calculateFinalScore(
                (float) $match->base_score,
                $result->score,
                $result->confidence,
            );

            $adjustment = MatchAiEvaluationService::calculateAdjustment($result->score, $result->confidence);

            $match->update([
                'ai_score' => round((float) $result->score, 2),
                'ai_confidence' => $result->confidence !== null ? round($result->confidence, 3) : null,
                'ai_status' => 'SUCCESS',
                'ai_provider' => $result->provider,
                'ai_model' => $result->model,
                'ai_summary' => $result->summary,
                'ai_evaluated_at' => now(),
                'final_score' => $finalScore,
            ]);

            Log::info('match_ai_success', [
                'match_id' => $match->id,
                'ai_score' => $result->score,
                'ai_confidence' => $result->confidence,
                'adjustment' => $adjustment,
                'final_score' => $finalScore,
                'provider' => $result->provider,
                'model' => $result->model,
                'latency_ms' => $latencyMs,
            ]);

            return;
        }

        $match->update([
            'ai_status' => 'FAILED',
            'ai_provider' => $result->provider,
            'ai_model' => $result->model,
            'ai_evaluated_at' => now(),
        ]);

        Log::info('match_ai_failed', [
            'match_id' => $match->id,
            'provider' => $result->provider,
            'model' => $result->model,
            'latency_ms' => $latencyMs,
        ]);
    }
}
