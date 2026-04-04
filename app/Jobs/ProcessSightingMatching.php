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
            }
        }

        foreach ($newMatchesByReport as $report) {
            $report->user->notify(new PetMatchesFound($report, 1));
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
