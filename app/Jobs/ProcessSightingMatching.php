<?php

namespace App\Jobs;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Models\PetMatch;
use App\Models\PetReport;
use App\Models\PetSighting;
use App\Notifications\PetMatchesFound;
use App\Services\MatchScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessSightingMatching implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    public $tries = 3;

    /**
     * @var array<int, int>
     */
    public $backoff = [10, 60];

    private const MAX_MATCHES_PER_REPORT = 20;

    public function __construct(
        private readonly PetSighting $sighting,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MatchScoringService $scorer): void
    {
        $lock = Cache::lock("sighting-matching:sighting:{$this->sighting->id}", 60);

        if (! $lock->get()) {
            $this->release(10);

            return;
        }

        try {
            $this->processMatching($scorer);
        } finally {
            $lock->release();
        }
    }

    private function processMatching(MatchScoringService $scorer): void
    {
        $sighting = PetSighting::query()
            ->withCoordinates()
            ->with(['characteristics'])
            ->findOrFail($this->sighting->id);

        if ($sighting->trashed()) {
            return;
        }

        $reports = $this->findCandidateReports($sighting);

        $newMatchesByReport = [];
        $affectedReports = [];

        foreach ($reports as $report) {
            $existing = PetMatch::query()
                ->where('report_id', $report->id)
                ->where('sighting_id', $sighting->id)
                ->first();

            if ($existing && in_array($existing->status, [PetMatchStatus::Confirmed, PetMatchStatus::Dismissed])) {
                continue;
            }

            $lostPet = $report->pet;
            if (! $lostPet) {
                continue;
            }

            $result = $scorer->calculateScore($sighting, $lostPet, $report->distance_meters);

            if ($result->total < MatchScoringService::SCORE_THRESHOLD) {
                continue;
            }

            if ($existing) {
                $score = round($result->total, 2);
                $existing->update([
                    'base_score' => $score,
                    'final_score' => $score,
                    'distance_meters' => $report->distance_meters,
                ]);
                $affectedReports[$report->id] = $report;
            } else {
                $currentCount = PetMatch::where('report_id', $report->id)
                    ->where('status', PetMatchStatus::Pending)
                    ->count();

                if ($currentCount >= self::MAX_MATCHES_PER_REPORT) {
                    continue;
                }

                $score = round($result->total, 2);
                PetMatch::create([
                    'report_id' => $report->id,
                    'sighting_id' => $sighting->id,
                    'base_score' => $score,
                    'final_score' => $score,
                    'distance_meters' => $report->distance_meters,
                    'status' => PetMatchStatus::Pending,
                ]);

                $newMatchesByReport[$report->id] = $report;
                $affectedReports[$report->id] = $report;
            }
        }

        foreach ($newMatchesByReport as $report) {
            $report->user->notify(new PetMatchesFound($report, 1));
        }

        if (config('services.match_ai.enabled')) {
            foreach ($affectedReports as $report) {
                $this->dispatchAiEvaluations($report);
            }
        }
    }

    /**
     * Seleciona matches PENDING elegiveis e despacha avaliacao por IA.
     */
    private function dispatchAiEvaluations(PetReport $report): void
    {
        $minScore = (int) config('services.match_ai.min_base_score', 25);
        $maxPerReport = (int) config('services.match_ai.max_evaluations_per_report', 5);

        $eligibleMatches = PetMatch::query()
            ->where('report_id', $report->id)
            ->where('status', PetMatchStatus::Pending)
            ->whereNull('ai_status')
            ->where('base_score', '>=', $minScore)
            ->orderByDesc('base_score')
            ->orderBy('distance_meters')
            ->limit($maxPerReport)
            ->get();

        Log::info('match_ai_dispatched', [
            'report_id' => $report->id,
            'eligible_count' => $eligibleMatches->count(),
        ]);

        foreach ($eligibleMatches as $match) {
            ProcessMatchAiEvaluation::dispatch($match)
                ->delay(now()->addSeconds(5));
        }
    }

    /**
     * Find candidate lost reports within radius matching the same species.
     *
     * @return Collection<int, PetReport>
     */
    private function findCandidateReports(PetSighting $sighting): Collection
    {
        return PetReport::query()
            ->select('pet_reports.*')
            ->selectRaw(
                'ST_Distance(pet_reports.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_meters',
                [$sighting->longitude, $sighting->latitude]
            )
            ->where('pet_reports.status', PetReportStatus::Lost)
            ->where('pet_reports.is_active', true)
            ->where('pet_reports.user_id', '!=', $sighting->user_id)
            ->whereNotNull('pet_reports.location')
            ->whereHas('pet', fn ($q) => $q->where('species', $sighting->species)->whereNull('deleted_at'))
            ->whereRaw(
                'ST_DWithin(pet_reports.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$sighting->longitude, $sighting->latitude, MatchScoringService::MAX_RADIUS_METERS]
            )
            ->with(['pet.characteristics', 'user.notificationSetting'])
            ->get();
    }
}
