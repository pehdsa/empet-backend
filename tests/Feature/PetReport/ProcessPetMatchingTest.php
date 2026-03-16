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
