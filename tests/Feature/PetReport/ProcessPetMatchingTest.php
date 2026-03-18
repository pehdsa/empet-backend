<?php

namespace Tests\Feature\PetReport;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use App\Jobs\ProcessPetMatching;
use App\Models\Breed;
use App\Models\Characteristic;
use App\Models\Pet;
use App\Models\PetMatch;
use App\Models\PetReport;
use App\Models\User;
use App\Notifications\PetMatchesFound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProcessPetMatchingTest extends TestCase
{
    use RefreshDatabase;

    private function createReportWithLocation(array $attributes, float $lng = -43.1729, float $lat = -22.9068): PetReport
    {
        $report = PetReport::factory()->create($attributes);
        DB::statement('UPDATE pet_reports SET location = ST_MakePoint(?, ?)::geography WHERE id = ?', [$lng, $lat, $report->id]);

        return $report->fresh();
    }

    private function createNearbyCandidate(User $user, array $petAttributes = [], float $lng = -43.1730, float $lat = -22.9069): Pet
    {
        $pet = Pet::factory()->create(array_merge(['user_id' => $user->id], $petAttributes));
        $this->createReportWithLocation(
            ['user_id' => $user->id, 'pet_id' => $pet->id, 'status' => PetReportStatus::Lost],
            $lng, $lat
        );

        return $pet;
    }

    // ──────────────────────────────────────────────
    // BASIC MATCHING
    // ──────────────────────────────────────────────

    public function test_creates_matches_for_nearby_same_species_pets(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id, 'breed_id' => $breed->id, 'size' => PetSize::Medium, 'sex' => PetSex::Male]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        $finderUser = User::factory()->client()->create();
        $this->createNearbyCandidate($finderUser, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        $this->assertDatabaseHas('pet_matches', [
            'report_id' => $report->id,
            'status' => PetMatchStatus::Pending->value,
        ]);
    }

    public function test_does_not_match_different_species(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        $finderUser = User::factory()->client()->create();
        $this->createNearbyCandidate($finderUser, ['species' => PetSpecies::Cat]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        $this->assertEquals(0, PetMatch::where('report_id', $report->id)->count());
    }

    public function test_does_not_match_own_pets(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id, 'breed_id' => $breed->id]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        // Same owner has another pet nearby — should NOT match
        $this->createNearbyCandidate($owner, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
        ]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        $this->assertEquals(0, PetMatch::where('report_id', $report->id)->count());
    }

    public function test_does_not_match_inactive_pets(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        $finderUser = User::factory()->client()->create();
        $this->createNearbyCandidate($finderUser, [
            'species' => PetSpecies::Dog,
            'is_active' => false,
        ]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        $this->assertEquals(0, PetMatch::where('report_id', $report->id)->count());
    }

    public function test_does_not_match_far_away_pets(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id, 'breed_id' => $breed->id, 'size' => PetSize::Medium, 'sex' => PetSex::Male]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id], -43.1729, -22.9068);

        // São Paulo is ~350km away from Rio
        $finderUser = User::factory()->client()->create();
        $this->createNearbyCandidate($finderUser, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ], -46.6333, -23.5505);

        $job = new ProcessPetMatching($report);
        $job->handle();

        $this->assertEquals(0, PetMatch::where('report_id', $report->id)->count());
    }

    public function test_skips_dismissed_matches(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id, 'breed_id' => $breed->id, 'size' => PetSize::Medium, 'sex' => PetSex::Male]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        $finderUser = User::factory()->client()->create();
        $candidate = $this->createNearbyCandidate($finderUser, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);

        PetMatch::factory()->dismissed()->create(['report_id' => $report->id, 'matched_pet_id' => $candidate->id]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        // Should only have the pre-existing dismissed, no new pending
        $this->assertEquals(0, PetMatch::where('report_id', $report->id)->where('status', PetMatchStatus::Pending)->count());
    }

    // ──────────────────────────────────────────────
    // SCORING
    // ──────────────────────────────────────────────

    public function test_exact_breed_scores_higher_than_no_breed(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        $finderUser1 = User::factory()->client()->create();
        $exactBreedPet = $this->createNearbyCandidate($finderUser1, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);

        $finderUser2 = User::factory()->client()->create();
        $otherBreed = Breed::factory()->dog()->create();
        $noMatchBreedPet = $this->createNearbyCandidate($finderUser2, [
            'species' => PetSpecies::Dog,
            'breed_id' => $otherBreed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        $matches = PetMatch::where('report_id', $report->id)->orderByDesc('score')->get();
        $this->assertGreaterThanOrEqual(2, $matches->count());

        $exactMatch = $matches->firstWhere('matched_pet_id', $exactBreedPet->id);
        $otherMatch = $matches->firstWhere('matched_pet_id', $noMatchBreedPet->id);

        $this->assertNotNull($exactMatch);
        $this->assertNotNull($otherMatch);
        $this->assertGreaterThan((float) $otherMatch->score, (float) $exactMatch->score);
    }

    public function test_breed_score_returns_full_score_when_primary_breeds_match(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        $finderUser = User::factory()->client()->create();
        $candidate = $this->createNearbyCandidate($finderUser, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);

        (new ProcessPetMatching($report))->handle();

        $match = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidate->id)->first();
        $this->assertNotNull($match);
        // proximity (~11m ≈ 35) + breed exact (25) + size (10) + sex (10) + color (10) + characteristics (5) = ~95
        $this->assertGreaterThan(90, (float) $match->score);
    }

    public function test_breed_score_returns_partial_when_primary_and_secondary_overlap(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breedA = Breed::factory()->dog()->create();
        $breedB = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breedA->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        // Candidate whose secondary breed matches lost pet's primary breed
        $finderUser = User::factory()->client()->create();
        $candidate = $this->createNearbyCandidate($finderUser, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breedB->id,
            'secondary_breed_id' => $breedA->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);

        (new ProcessPetMatching($report))->handle();

        $match = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidate->id)->first();
        $this->assertNotNull($match);
        // proximity (~11m ≈ 35) + breed partial (12) + size (10) + sex (10) + color (0) + characteristics (5) = ~72
        $this->assertGreaterThan(60, (float) $match->score);
    }

    public function test_breed_score_returns_zero_when_one_pet_has_no_breed(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        // Candidate without any breed (uncertainty — should be neutral, not penalised).
        // The factory afterCreating hook always assigns a breed, so we force null after creation.
        $finderUserNoBreed = User::factory()->client()->create();
        $candidateNoBreed = $this->createNearbyCandidate($finderUserNoBreed, [
            'species' => PetSpecies::Dog,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);
        Pet::where('id', $candidateNoBreed->id)->update(['breed_id' => null, 'secondary_breed_id' => null]);

        // Candidate with exact breed match (reference)
        $finderUserExact = User::factory()->client()->create();
        $candidateExact = $this->createNearbyCandidate($finderUserExact, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);

        (new ProcessPetMatching($report))->handle();

        $matchNoBreed = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidateNoBreed->id)->first();
        $matchExact = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidateExact->id)->first();

        // Both appear (no penalty for missing breed)
        $this->assertNotNull($matchNoBreed);
        $this->assertNotNull($matchExact);
        // Exact breed scores higher, but no-breed candidate is not penalised
        $this->assertGreaterThan((float) $matchNoBreed->score, (float) $matchExact->score);
    }

    public function test_breed_score_returns_negative_when_known_breed_sets_have_no_overlap(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breedA = Breed::factory()->dog()->create();
        $breedB = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breedA->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        // Candidate with a completely different known breed (no overlap)
        $finderUserMismatch = User::factory()->client()->create();
        $candidateMismatch = $this->createNearbyCandidate($finderUserMismatch, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breedB->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);

        // Candidate with no breed (uncertainty — neutral, no penalty).
        // The factory afterCreating hook always assigns a breed, so we force null after creation.
        $finderUserNoBreed = User::factory()->client()->create();
        $candidateNoBreed = $this->createNearbyCandidate($finderUserNoBreed, [
            'species' => PetSpecies::Dog,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);
        Pet::where('id', $candidateNoBreed->id)->update(['breed_id' => null, 'secondary_breed_id' => null]);

        (new ProcessPetMatching($report))->handle();

        $matchMismatch = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidateMismatch->id)->first();
        $matchNoBreed = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidateNoBreed->id)->first();

        $this->assertNotNull($matchMismatch);
        $this->assertNotNull($matchNoBreed);
        // Known mismatch (-15) must score lower than unknown breed (0)
        $this->assertGreaterThan((float) $matchMismatch->score, (float) $matchNoBreed->score);
    }

    public function test_sex_score_ranks_known_match_above_unknown_above_mismatch(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        // Known matching sex → +10
        $finderUser1 = User::factory()->client()->create();
        $candidateSameKnown = $this->createNearbyCandidate($finderUser1, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);

        // One unknown → +5 (UNKNOWN must not return +10 from equality check)
        $finderUser2 = User::factory()->client()->create();
        $candidateUnknown = $this->createNearbyCandidate($finderUser2, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Unknown,
            'primary_color' => 'brown',
        ]);

        // Known mismatching sex → -10
        $finderUser3 = User::factory()->client()->create();
        $candidateOppositeSex = $this->createNearbyCandidate($finderUser3, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Female,
            'primary_color' => 'brown',
        ]);

        (new ProcessPetMatching($report))->handle();

        $matchSameKnown = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidateSameKnown->id)->first();
        $matchUnknown = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidateUnknown->id)->first();
        $matchOpposite = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidateOppositeSex->id)->first();

        $this->assertNotNull($matchSameKnown);
        $this->assertNotNull($matchUnknown);
        $this->assertNotNull($matchOpposite);

        // +10 > +5 > -10
        $this->assertGreaterThan((float) $matchUnknown->score, (float) $matchSameKnown->score);
        $this->assertGreaterThan((float) $matchOpposite->score, (float) $matchUnknown->score);
        // Score deltas must match expected differences: +10 vs +5 = 5pts, +5 vs -10 = 15pts
        $this->assertEqualsWithDelta(5.0, (float) $matchSameKnown->score - (float) $matchUnknown->score, 0.01);
        $this->assertEqualsWithDelta(15.0, (float) $matchUnknown->score - (float) $matchOpposite->score, 0.01);
    }

    public function test_sex_score_returns_negative_when_both_known_and_different(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        // Opposite sex (strong negative signal)
        $finderUserOpposite = User::factory()->client()->create();
        $candidateOppositeSex = $this->createNearbyCandidate($finderUserOpposite, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Female,
        ]);

        // Same sex (positive signal)
        $finderUserSame = User::factory()->client()->create();
        $candidateSameSex = $this->createNearbyCandidate($finderUserSame, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);

        (new ProcessPetMatching($report))->handle();

        $matchOpposite = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidateOppositeSex->id)->first();
        $matchSame = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $candidateSameSex->id)->first();

        $this->assertNotNull($matchOpposite);
        $this->assertNotNull($matchSame);
        // Sex mismatch (-10) must score lower than sex match (+10)
        $this->assertGreaterThan((float) $matchOpposite->score, (float) $matchSame->score);
    }

    public function test_matching_ranks_breed_compatible_candidate_above_breed_mismatch(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breedA = Breed::factory()->dog()->create();
        $breedB = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breedA->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        $finderUser1 = User::factory()->client()->create();
        $compatibleBreedPet = $this->createNearbyCandidate($finderUser1, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breedA->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);

        $finderUser2 = User::factory()->client()->create();
        $mismatchBreedPet = $this->createNearbyCandidate($finderUser2, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breedB->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);

        (new ProcessPetMatching($report))->handle();

        $matchCompatible = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $compatibleBreedPet->id)->first();
        $matchMismatch = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $mismatchBreedPet->id)->first();

        $this->assertNotNull($matchCompatible);
        $this->assertNotNull($matchMismatch);
        $this->assertGreaterThan((float) $matchMismatch->score, (float) $matchCompatible->score);
    }

    public function test_matching_ranks_sex_compatible_candidate_above_sex_mismatch(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        $finderUser1 = User::factory()->client()->create();
        $compatibleSexPet = $this->createNearbyCandidate($finderUser1, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);

        $finderUser2 = User::factory()->client()->create();
        $mismatchSexPet = $this->createNearbyCandidate($finderUser2, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Female,
            'primary_color' => 'brown',
        ]);

        (new ProcessPetMatching($report))->handle();

        $matchCompatible = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $compatibleSexPet->id)->first();
        $matchMismatch = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $mismatchSexPet->id)->first();

        $this->assertNotNull($matchCompatible);
        $this->assertNotNull($matchMismatch);
        $this->assertGreaterThan((float) $matchMismatch->score, (float) $matchCompatible->score);
    }

    public function test_matching_excludes_candidate_with_breed_and_sex_mismatch_below_threshold(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breedA = Breed::factory()->dog()->create();
        $breedB = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breedA->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);
        // Place report far enough that proximity alone cannot compensate penalties
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id], -43.1729, -22.9068);

        $finderUser = User::factory()->client()->create();
        // 15km away, different breed (-15) and different sex (-10)
        // proximity ~17.5 + breed -15 + size 10 + sex -10 + color 0 + chars 5 = ~7.5 — below threshold
        $this->createNearbyCandidate($finderUser, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breedB->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Female,
        ], -43.3200, -22.9068); // ~15km west

        (new ProcessPetMatching($report))->handle();

        $this->assertEquals(0, PetMatch::where('report_id', $report->id)->count());
    }

    // ──────────────────────────────────────────────
    // STATUS & LOCK
    // ──────────────────────────────────────────────

    public function test_skips_cancelled_report(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation([
            'user_id' => $owner->id,
            'pet_id' => $lostPet->id,
            'status' => PetReportStatus::Cancelled,
        ]);

        $finderUser = User::factory()->client()->create();
        $this->createNearbyCandidate($finderUser, ['species' => PetSpecies::Dog]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        $this->assertEquals(0, PetMatch::where('report_id', $report->id)->count());
    }

    public function test_skips_found_report(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation([
            'user_id' => $owner->id,
            'pet_id' => $lostPet->id,
            'status' => PetReportStatus::Found,
            'found_at' => now(),
        ]);

        $finderUser = User::factory()->client()->create();
        $this->createNearbyCandidate($finderUser, ['species' => PetSpecies::Dog]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        $this->assertEquals(0, PetMatch::where('report_id', $report->id)->count());
    }

    public function test_skips_inactive_report(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation([
            'user_id' => $owner->id,
            'pet_id' => $lostPet->id,
            'is_active' => false,
        ]);

        $finderUser = User::factory()->client()->create();
        $this->createNearbyCandidate($finderUser, ['species' => PetSpecies::Dog]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        $this->assertEquals(0, PetMatch::where('report_id', $report->id)->count());
    }

    // ──────────────────────────────────────────────
    // NOTIFICATION
    // ──────────────────────────────────────────────

    public function test_sends_notification_when_matches_found(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id, 'breed_id' => $breed->id, 'size' => PetSize::Medium, 'sex' => PetSex::Male]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        $finderUser = User::factory()->client()->create();
        $this->createNearbyCandidate($finderUser, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
        ]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        Notification::assertSentTo($owner, PetMatchesFound::class);
    }

    public function test_does_not_send_notification_when_no_matches(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $lostPet = Pet::factory()->dog()->create(['user_id' => $owner->id]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        // No candidates at all

        $job = new ProcessPetMatching($report);
        $job->handle();

        Notification::assertNothingSent();
    }

    // ──────────────────────────────────────────────
    // CHARACTERISTICS MATCHING
    // ──────────────────────────────────────────────

    public function test_shared_characteristics_increase_score(): void
    {
        Notification::fake();

        $owner = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $char1 = Characteristic::factory()->create();
        $char2 = Characteristic::factory()->create();

        $lostPet = Pet::factory()->dog()->create([
            'user_id' => $owner->id,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);
        $lostPet->characteristics()->attach([$char1->id, $char2->id]);
        $report = $this->createReportWithLocation(['user_id' => $owner->id, 'pet_id' => $lostPet->id]);

        // Candidate with same characteristics
        $finderUser1 = User::factory()->client()->create();
        $petWithChars = $this->createNearbyCandidate($finderUser1, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);
        $petWithChars->characteristics()->attach([$char1->id, $char2->id]);

        // Candidate without characteristics
        $finderUser2 = User::factory()->client()->create();
        $petNoChars = $this->createNearbyCandidate($finderUser2, [
            'species' => PetSpecies::Dog,
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'brown',
        ]);

        $job = new ProcessPetMatching($report);
        $job->handle();

        $matchWithChars = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $petWithChars->id)->first();
        $matchNoChars = PetMatch::where('report_id', $report->id)->where('matched_pet_id', $petNoChars->id)->first();

        $this->assertNotNull($matchWithChars);
        $this->assertNotNull($matchNoChars);
        $this->assertGreaterThan((float) $matchNoChars->score, (float) $matchWithChars->score);
    }
}
