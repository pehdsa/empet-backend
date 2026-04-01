<?php

namespace App\Jobs;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Enums\PetSex;
use App\Models\Pet;
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

    private const MAX_RADIUS_METERS = 25000;

    private const SCORE_THRESHOLD = 30;

    private const MAX_MATCHES = 20;

    private const COLOR_STOPWORDS = ['e', 'com', 'de', 'o', 'a'];

    public function __construct(
        private readonly PetReport $report,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lock = Cache::lock("report-sighting-matching:report:{$this->report->id}", 60);

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

            $score = $this->calculateScore($sighting, $lostPet, $sighting->distance_meters);

            if ($score < self::SCORE_THRESHOLD) {
                continue;
            }

            if ($existing) {
                $existing->update([
                    'score' => round($score, 2),
                    'distance_meters' => $sighting->distance_meters,
                ]);
            } else {
                $matches[] = [
                    'sighting' => $sighting,
                    'score' => round($score, 2),
                    'distance' => $sighting->distance_meters,
                ];
                $newCount++;
            }
        }

        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);
        $matches = array_slice($matches, 0, self::MAX_MATCHES);

        foreach ($matches as $match) {
            PetMatch::create([
                'report_id' => $report->id,
                'sighting_id' => $match['sighting']->id,
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
            ->whereNull('pet_sightings.deleted_at')
            ->whereRaw(
                'ST_DWithin(pet_sightings.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$report->longitude, $report->latitude, self::MAX_RADIUS_METERS]
            )
            ->with(['characteristics'])
            ->get();
    }

    /**
     * Calculate matching score between sighting and lost pet.
     */
    private function calculateScore(PetSighting $sighting, Pet $lostPet, ?float $distance): float
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

    private function proximityScore(?float $distance): float
    {
        if ($distance === null) {
            return 0;
        }

        return max(0, 35 * (1 - $distance / self::MAX_RADIUS_METERS));
    }

    private function breedScore(PetSighting $sighting, Pet $lostPet): float
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

    private function sizeScore(PetSighting $sighting, Pet $lostPet): float
    {
        $sizeOrder = ['SMALL' => 0, 'MEDIUM' => 1, 'LARGE' => 2];
        $sightingSize = $sighting->size ? ($sizeOrder[$sighting->size->value] ?? null) : null;
        $lostSize = $lostPet->size ? ($sizeOrder[$lostPet->size->value] ?? null) : null;

        if ($sightingSize === null || $lostSize === null) {
            return 0;
        }

        return match (abs($sightingSize - $lostSize)) {
            0 => 10,
            1 => 4,
            default => 0,
        };
    }

    private function sexScore(PetSighting $sighting, Pet $lostPet): float
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

    private function colorScore(PetSighting $sighting, Pet $lostPet): float
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

        return $union === 0 ? 0 : ($intersection / $union) * 5;
    }

    /**
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

    private function characteristicsScore(PetSighting $sighting, Pet $lostPet): float
    {
        $sightingIds = $sighting->characteristics->pluck('id')->toArray();
        $lostIds = $lostPet->characteristics->pluck('id')->toArray();

        if (empty($sightingIds) && empty($lostIds)) {
            return 5;
        }

        $intersection = count(array_intersect($sightingIds, $lostIds));
        $union = count(array_unique(array_merge($sightingIds, $lostIds)));

        return $union === 0 ? 5 : ($intersection / $union) * 10;
    }
}
