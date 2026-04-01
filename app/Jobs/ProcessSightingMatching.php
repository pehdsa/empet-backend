<?php

namespace App\Jobs;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Enums\PetSex;
use App\Models\PetMatch;
use App\Models\PetReport;
use App\Models\PetSighting;
use App\Notifications\PetMatchesFound;
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

    private const MAX_RADIUS_METERS = 25000;

    private const SCORE_THRESHOLD = 30;

    private const MAX_MATCHES_PER_REPORT = 20;

    private const COLOR_STOPWORDS = ['e', 'com', 'de', 'o', 'a'];

    public function __construct(
        private readonly PetSighting $sighting,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lock = Cache::lock("sighting-matching:sighting:{$this->sighting->id}", 60);

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

            $score = $this->calculateScore($sighting, $lostPet, $report->distance_meters);

            if ($score < self::SCORE_THRESHOLD) {
                continue;
            }

            if ($existing) {
                $existing->update([
                    'score' => round($score, 2),
                    'distance_meters' => $report->distance_meters,
                ]);
            } else {
                $currentCount = PetMatch::where('report_id', $report->id)
                    ->where('status', PetMatchStatus::Pending)
                    ->count();

                if ($currentCount >= self::MAX_MATCHES_PER_REPORT) {
                    continue;
                }

                PetMatch::create([
                    'report_id' => $report->id,
                    'sighting_id' => $sighting->id,
                    'score' => round($score, 2),
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
            ->whereNotNull('pet_reports.location')
            ->whereHas('pet', fn ($q) => $q->where('species', $sighting->species)->whereNull('deleted_at'))
            ->whereRaw(
                'ST_DWithin(pet_reports.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$sighting->longitude, $sighting->latitude, self::MAX_RADIUS_METERS]
            )
            ->with(['pet.characteristics', 'user.notificationSetting'])
            ->get();
    }

    /**
     * Calculate matching score between sighting and lost pet.
     */
    private function calculateScore(PetSighting $sighting, $lostPet, ?float $distance): float
    {
        $score = 0;

        $score += $this->proximityScore($distance);
        $score += $this->breedScore($sighting, $lostPet);
        $score += $this->sizeScore($sighting, $lostPet);
        $score += $this->sexScore($sighting, $lostPet);
        $score += $this->colorScore($sighting, $lostPet);
        $score += $this->characteristicsScore($sighting, $lostPet);

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
     * Breed score based on sighting breed vs pet breeds.
     *
     * Both unknown = +5, one unknown = 0,
     * primary match = +25, secondary match = +12,
     * known mismatch = -15.
     */
    private function breedScore(PetSighting $sighting, $lostPet): float
    {
        $sightingBreed = $sighting->breed_id;

        $lostBreeds = array_values(array_filter(
            [$lostPet->breed_id, $lostPet->secondary_breed_id ?? null],
            fn ($id) => $id !== null
        ));

        if ($sightingBreed === null && empty($lostBreeds)) {
            return 5;
        }

        if ($sightingBreed === null || empty($lostBreeds)) {
            return 0;
        }

        if ($lostPet->breed_id !== null && $sightingBreed === $lostPet->breed_id) {
            return 25;
        }

        if (in_array($sightingBreed, $lostBreeds)) {
            return 12;
        }

        return -15;
    }

    /**
     * Size score: exact = 10, 1 level diff = 4, 2+ levels = 0.
     */
    private function sizeScore(PetSighting $sighting, $lostPet): float
    {
        $sizeOrder = ['SMALL' => 0, 'MEDIUM' => 1, 'LARGE' => 2];

        $sightingSize = $sighting->size ? ($sizeOrder[$sighting->size->value] ?? null) : null;
        $lostSize = $lostPet->size ? ($sizeOrder[$lostPet->size->value] ?? null) : null;

        if ($sightingSize === null || $lostSize === null) {
            return 0;
        }

        $diff = abs($sightingSize - $lostSize);

        return match ($diff) {
            0 => 10,
            1 => 4,
            default => 0,
        };
    }

    /**
     * Sex score: both known and equal = +10, at least one unknown = +5,
     * both known and different = -10.
     */
    private function sexScore(PetSighting $sighting, $lostPet): float
    {
        $sightingSex = $sighting->sex;
        $lostSex = $lostPet->sex;

        if ($sightingSex === null || $lostSex === null) {
            return 0;
        }

        if ($sightingSex === PetSex::Unknown || $lostSex === PetSex::Unknown) {
            return 5;
        }

        if ($sightingSex === $lostSex) {
            return 10;
        }

        return -10;
    }

    /**
     * Color score using token intersection.
     */
    private function colorScore(PetSighting $sighting, $lostPet): float
    {
        $sightingColor = $sighting->color;
        $lostColor = $lostPet->primary_color;

        if ($sightingColor === null && $lostColor === null) {
            return 3;
        }

        if ($sightingColor === null || $lostColor === null) {
            return 0;
        }

        $sightingTokens = $this->colorTokens($sightingColor);
        $lostTokens = $this->colorTokens($lostColor);

        if (empty($sightingTokens) || empty($lostTokens)) {
            return 0;
        }

        $intersection = count(array_intersect($sightingTokens, $lostTokens));
        $union = count(array_unique(array_merge($sightingTokens, $lostTokens)));

        if ($union === 0) {
            return 0;
        }

        return ($intersection / $union) * 5;
    }

    /**
     * Normalize and tokenize a color string, discarding stopwords.
     *
     * @return array<int, string>
     */
    private function colorTokens(string $color): array
    {
        $normalized = mb_strtolower(trim($color));
        $tokens = preg_split('/[\s,\/]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(
            $tokens,
            fn ($token) => ! in_array($token, self::COLOR_STOPWORDS)
        ));
    }

    /**
     * Characteristics score: Jaccard index * 10. Both empty = 5.
     */
    private function characteristicsScore(PetSighting $sighting, $lostPet): float
    {
        $sightingIds = $sighting->characteristics->pluck('id')->toArray();
        $lostIds = $lostPet->characteristics->pluck('id')->toArray();

        if (empty($sightingIds) && empty($lostIds)) {
            return 5;
        }

        $intersection = count(array_intersect($sightingIds, $lostIds));
        $union = count(array_unique(array_merge($sightingIds, $lostIds)));

        if ($union === 0) {
            return 5;
        }

        return ($intersection / $union) * 10;
    }
}
