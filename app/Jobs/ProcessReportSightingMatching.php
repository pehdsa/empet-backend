<?php

namespace App\Jobs;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Models\Pet;
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

class ProcessReportSightingMatching implements ShouldQueue
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

    private const MAX_MATCHES = 20;

    public function __construct(
        private readonly PetReport $report,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MatchScoringService $scorer): void
    {
        $lock = Cache::lock("report-sighting-matching:report:{$this->report->id}", 60);

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
        $report = PetReport::query()
            ->withCoordinates()
            ->findOrFail($this->report->id);

        if ($report->status !== PetReportStatus::Lost || ! $report->is_active) {
            return;
        }

        $lostPet = Pet::query()
            ->with(['characteristics'])
            ->findOrFail($report->pet_id);

        $sightings = $this->findCandidateSightings($report, $lostPet);

        $matches = [];
        $newCount = 0;

        foreach ($sightings as $sighting) {
            $existing = PetMatch::query()
                ->where('report_id', $report->id)
                ->where('sighting_id', $sighting->id)
                ->first();

            if ($existing && in_array($existing->status, [PetMatchStatus::Confirmed, PetMatchStatus::Dismissed])) {
                continue;
            }

            $result = $scorer->calculateScore($sighting, $lostPet, $sighting->distance_meters);

            if ($result->total < MatchScoringService::SCORE_THRESHOLD) {
                continue;
            }

            if ($existing) {
                $score = round($result->total, 2);
                $existing->update([
                    'base_score' => $score,
                    'final_score' => $score,
                    'distance_meters' => $sighting->distance_meters,
                ]);
            } else {
                $matches[] = [
                    'sighting' => $sighting,
                    'base_score' => round($result->total, 2),
                    'distance' => $sighting->distance_meters,
                ];
                $newCount++;
            }
        }

        usort($matches, fn ($a, $b) => $b['base_score'] <=> $a['base_score']);
        $matches = array_slice($matches, 0, self::MAX_MATCHES);

        foreach ($matches as $match) {
            PetMatch::create([
                'report_id' => $report->id,
                'sighting_id' => $match['sighting']->id,
                'base_score' => $match['base_score'],
                'final_score' => $match['base_score'],
                'distance_meters' => $match['distance'],
                'status' => PetMatchStatus::Pending,
            ]);
        }

        if (count($matches) > 0) {
            $report->user->notify(new PetMatchesFound($report, count($matches)));
        }
    }

    /**
     * Find candidate sightings within radius matching the same species.
     *
     * @return Collection<int, PetSighting>
     */
    private function findCandidateSightings(PetReport $report, Pet $lostPet): Collection
    {
        return PetSighting::query()
            ->select('pet_sightings.*')
            ->selectRaw(
                'ST_Distance(pet_sightings.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_meters',
                [$report->longitude, $report->latitude]
            )
            ->where('pet_sightings.species', $lostPet->species)
            ->where('pet_sightings.user_id', '!=', $report->user_id)
            ->whereNull('pet_sightings.deleted_at')
            ->whereRaw(
                'ST_DWithin(pet_sightings.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$report->longitude, $report->latitude, MatchScoringService::MAX_RADIUS_METERS]
            )
            ->with(['characteristics'])
            ->get();
    }
}
