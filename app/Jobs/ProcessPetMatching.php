<?php

namespace App\Jobs;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Enums\PetSex;
use App\Models\Pet;
use App\Models\PetMatch;
use App\Models\PetReport;
use App\Notifications\PetMatchesFound;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProcessPetMatching implements ShouldQueue
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

    private const MAX_RADIUS_METERS = 25000;

    private const SCORE_THRESHOLD = 30;

    private const MAX_MATCHES = 20;

    public function __construct(
        private readonly PetReport $report,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lock = Cache::lock("pet-matching:report:{$this->report->id}", 60);

        if (! $lock->get()) {
            $this->release(10);

            return;
        }

        try {
            $this->processMatching();
        } finally {
            $lock->release();
        }
    }

    private function processMatching(): void
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

        $dismissedPetIds = PetMatch::query()
            ->where('report_id', $report->id)
            ->where('status', PetMatchStatus::Dismissed)
            ->pluck('matched_pet_id')
            ->toArray();

        $candidates = $this->findCandidates($report, $lostPet, $dismissedPetIds);

        $matches = [];
        foreach ($candidates as $candidate) {
            $score = $this->calculateScore($lostPet, $candidate, $candidate->distance_meters);

            if ($score >= self::SCORE_THRESHOLD) {
                $matches[] = [
                    'pet' => $candidate,
                    'score' => round($score, 2),
                    'distance' => $candidate->distance_meters,
                ];
            }
        }

        usort($matches, function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            if ($a['distance'] !== $b['distance']) {
                return $a['distance'] <=> $b['distance'];
            }

            return $a['pet']->id <=> $b['pet']->id;
        });

        $matches = array_slice($matches, 0, self::MAX_MATCHES);

        foreach ($matches as $match) {
            PetMatch::create([
                'report_id' => $report->id,
                'matched_pet_id' => $match['pet']->id,
                'score' => $match['score'],
                'distance_meters' => $match['distance'],
                'status' => PetMatchStatus::Pending,
            ]);
        }

        if (count($matches) > 0) {
            $report->user->notify(new PetMatchesFound($report, count($matches)));
        }
    }

    /**
     * Find candidate pets for matching.
     *
     * @param  array<int>  $dismissedPetIds
     * @return Collection<int, Pet>
     */
    private function findCandidates(PetReport $report, Pet $lostPet, array $dismissedPetIds)
    {
        return Pet::query()
            ->select('pets.*')
            ->selectRaw(
                'MIN(ST_Distance(pr.location, ST_MakePoint(?, ?)::geography)) as distance_meters',
                [$report->longitude, $report->latitude]
            )
            ->join('pet_reports as pr', 'pets.user_id', '=', 'pr.user_id')
            ->where('pets.species', $lostPet->species)
            ->where('pets.is_active', true)
            ->whereNull('pets.deleted_at')
            ->where('pets.user_id', '!=', $lostPet->user_id)
            ->whereRaw(
                'ST_DWithin(pr.location, ST_MakePoint(?, ?)::geography, ?)',
                [$report->longitude, $report->latitude, self::MAX_RADIUS_METERS]
            )
            ->where('pr.is_active', true)
            ->when(! empty($dismissedPetIds), fn ($q) => $q->whereNotIn('pets.id', $dismissedPetIds))
            ->groupBy('pets.id')
            ->with(['characteristics'])
            ->get();
    }

    /**
     * Calculate matching score between lost pet and candidate.
     */
    private function calculateScore(Pet $lostPet, Pet $candidate, ?float $distance): float
    {
        $score = 0;

        $score += $this->proximityScore($distance);
        $score += $this->breedScore($lostPet, $candidate);
        $score += $this->sizeScore($lostPet, $candidate);
        $score += $this->sexScore($lostPet, $candidate);
        $score += $this->colorScore($lostPet, $candidate);
        $score += $this->characteristicsScore($lostPet, $candidate);

        return $score;
    }

    /**
     * Proximity score: 0m = 35pts, max_radius = 0pts.
     */
    private function proximityScore(?float $distance): float
    {
        if ($distance === null) {
            return 0;
        }

        return max(0, 35 * (1 - $distance / self::MAX_RADIUS_METERS));
    }

    /**
     * Breed score based on set intersection between known breeds of each pet.
     *
     * Both unknown = +5 (can't rule out)
     * One unknown = 0 (uncertainty — neutral)
     * Primary breeds match = +25
     * Any other overlap (primary vs secondary) = +12
     * Known breeds with no overlap = -15 (strong negative signal)
     *
     * Note: -15 is an initial heuristic and may be recalibrated after
     * observing real match quality in production.
     */
    private function breedScore(Pet $lostPet, Pet $candidate): float
    {
        $lostBreeds = array_values(array_filter(
            [$lostPet->breed_id, $lostPet->secondary_breed_id],
            fn ($id) => $id !== null
        ));
        $candidateBreeds = array_values(array_filter(
            [$candidate->breed_id, $candidate->secondary_breed_id],
            fn ($id) => $id !== null
        ));

        if (empty($lostBreeds) && empty($candidateBreeds)) {
            return 5;
        }

        if (empty($lostBreeds) || empty($candidateBreeds)) {
            return 0;
        }

        if ($lostPet->breed_id !== null && $candidate->breed_id !== null && $lostPet->breed_id === $candidate->breed_id) {
            return 25;
        }

        if (! empty(array_intersect($lostBreeds, $candidateBreeds))) {
            return 12;
        }

        return -15;
    }

    /**
     * Size score: exact = 10, 1 level diff = 4, 2+ levels = 0.
     */
    private function sizeScore(Pet $lostPet, Pet $candidate): float
    {
        $sizeOrder = ['SMALL' => 0, 'MEDIUM' => 1, 'LARGE' => 2];

        $lostSize = $sizeOrder[$lostPet->size->value] ?? null;
        $candidateSize = $sizeOrder[$candidate->size->value] ?? null;

        if ($lostSize === null || $candidateSize === null) {
            return 0;
        }

        $diff = abs($lostSize - $candidateSize);

        return match ($diff) {
            0 => 10,
            1 => 4,
            default => 0,
        };
    }

    /**
     * Sex score: both known and equal = +10, at least one unknown = +5,
     * both known and different = -10 (strong negative signal).
     *
     * Note: UNKNOWN check must come before equality check to avoid
     * UNKNOWN === UNKNOWN incorrectly returning +10.
     *
     * The -10 penalty equals 100% of this criterion's budget, reflecting
     * that sex is easy to identify reliably in the field. When strong
     * positive signals exist (e.g. exact breed match), the penalty adds
     * negative evidence without necessarily dropping below the threshold.
     *
     * Note: -10 is an initial heuristic and may be recalibrated after
     * observing real match quality in production.
     */
    private function sexScore(Pet $lostPet, Pet $candidate): float
    {
        if ($lostPet->sex === PetSex::Unknown || $candidate->sex === PetSex::Unknown) {
            return 5;
        }

        if ($lostPet->sex === $candidate->sex) {
            return 10;
        }

        return -10;
    }

    /**
     * Color score: exact = 10, both null = 3, no match = 0.
     */
    private function colorScore(Pet $lostPet, Pet $candidate): float
    {
        if ($lostPet->primary_color === null && $candidate->primary_color === null) {
            return 3;
        }

        if ($lostPet->primary_color !== null && $candidate->primary_color !== null
            && strtolower($lostPet->primary_color) === strtolower($candidate->primary_color)) {
            return 10;
        }

        return 0;
    }

    /**
     * Characteristics score: Jaccard index * 10. Both empty = 5.
     */
    private function characteristicsScore(Pet $lostPet, Pet $candidate): float
    {
        $lostIds = $lostPet->characteristics->pluck('id')->toArray();
        $candidateIds = $candidate->characteristics->pluck('id')->toArray();

        if (empty($lostIds) && empty($candidateIds)) {
            return 5;
        }

        $intersection = count(array_intersect($lostIds, $candidateIds));
        $union = count(array_unique(array_merge($lostIds, $candidateIds)));

        if ($union === 0) {
            return 5;
        }

        return ($intersection / $union) * 10;
    }
}
