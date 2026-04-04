<?php

namespace Tests\Unit;

use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Models\Breed;
use App\Models\Characteristic;
use App\Models\Pet;
use App\Models\PetSighting;
use App\Services\MatchScoringService;
use App\Support\Matching\MatchScoreResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private MatchScoringService $scorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scorer = new MatchScoringService;
    }

    // ──────────────────────────────────────────────
    // PROXIMITY
    // ──────────────────────────────────────────────

    public function test_proximity_score_is_max_at_zero_distance(): void
    {
        $result = $this->score(distance: 0);

        $this->assertEquals(MatchScoringService::PROXIMITY_MAX, $result->proximity);
    }

    public function test_proximity_score_is_zero_at_max_radius(): void
    {
        $result = $this->score(distance: MatchScoringService::MAX_RADIUS_METERS);

        $this->assertEquals(0, $result->proximity);
    }

    public function test_proximity_score_is_zero_when_null(): void
    {
        $result = $this->score(distance: null);

        $this->assertEquals(0, $result->proximity);
    }

    public function test_proximity_score_scales_linearly(): void
    {
        $half = MatchScoringService::MAX_RADIUS_METERS / 2;
        $result = $this->score(distance: $half);

        $this->assertEqualsWithDelta(MatchScoringService::PROXIMITY_MAX / 2, $result->proximity, 0.01);
    }

    // ──────────────────────────────────────────────
    // BREED
    // ──────────────────────────────────────────────

    public function test_breed_score_both_unknown_gives_partial(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['breed_id' => null]);
        $pet = Pet::factory()->dog()->create();
        // afterCreating auto-assigns breed, reset to null for "both unknown" scenario
        $pet->update(['breed_id' => null, 'secondary_breed_id' => null]);
        $pet->refresh();

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(5, $result->breed);
    }

    public function test_breed_score_primary_match_gives_max(): void
    {
        $breed = Breed::factory()->dog()->create();
        $sighting = PetSighting::factory()->dog()->create(['breed_id' => $breed->id]);
        $pet = Pet::factory()->dog()->create(['breed_id' => $breed->id]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(MatchScoringService::BREED_MAX, $result->breed);
    }

    public function test_breed_score_secondary_match_gives_partial(): void
    {
        $primaryBreed = Breed::factory()->dog()->create();
        $secondaryBreed = Breed::factory()->dog()->create();
        $sighting = PetSighting::factory()->dog()->create(['breed_id' => $secondaryBreed->id]);
        $pet = Pet::factory()->dog()->create(['breed_id' => $primaryBreed->id, 'secondary_breed_id' => $secondaryBreed->id]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(12, $result->breed);
    }

    public function test_breed_score_mismatch_is_negative(): void
    {
        $breed1 = Breed::factory()->dog()->create();
        $breed2 = Breed::factory()->dog()->create();
        $sighting = PetSighting::factory()->dog()->create(['breed_id' => $breed1->id]);
        $pet = Pet::factory()->dog()->create(['breed_id' => $breed2->id]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(-15, $result->breed);
    }

    public function test_breed_score_sighting_unknown_gives_zero(): void
    {
        $breed = Breed::factory()->dog()->create();
        $sighting = PetSighting::factory()->dog()->create(['breed_id' => null]);
        $pet = Pet::factory()->dog()->create(['breed_id' => $breed->id]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(0, $result->breed);
    }

    public function test_breed_score_pet_unknown_gives_zero(): void
    {
        $breed = Breed::factory()->dog()->create();
        $sighting = PetSighting::factory()->dog()->create(['breed_id' => $breed->id]);
        $pet = Pet::factory()->dog()->create();
        $pet->update(['breed_id' => null, 'secondary_breed_id' => null]);
        $pet->refresh();

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(0, $result->breed);
    }

    // ──────────────────────────────────────────────
    // SIZE
    // ──────────────────────────────────────────────

    public function test_size_score_exact_match_gives_max(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['size' => PetSize::Medium]);
        $pet = Pet::factory()->dog()->create(['size' => PetSize::Medium]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(MatchScoringService::SIZE_MAX, $result->size);
    }

    public function test_size_score_one_level_diff_gives_partial(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['size' => PetSize::Medium]);
        $pet = Pet::factory()->dog()->create(['size' => PetSize::Large]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(4, $result->size);
    }

    public function test_size_score_two_level_diff_gives_zero(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['size' => PetSize::Small]);
        $pet = Pet::factory()->dog()->create(['size' => PetSize::Large]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(0, $result->size);
    }

    public function test_size_score_sighting_null_gives_zero(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['size' => null]);
        $pet = Pet::factory()->dog()->create(['size' => PetSize::Medium]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(0, $result->size);
    }

    // ──────────────────────────────────────────────
    // SEX
    // ──────────────────────────────────────────────

    public function test_sex_score_match_gives_max(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['sex' => PetSex::Male]);
        $pet = Pet::factory()->dog()->create(['sex' => PetSex::Male]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(MatchScoringService::SEX_MAX, $result->sex);
    }

    public function test_sex_score_mismatch_is_negative(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['sex' => PetSex::Male]);
        $pet = Pet::factory()->dog()->create(['sex' => PetSex::Female]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(-10, $result->sex);
    }

    public function test_sex_score_unknown_gives_partial(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['sex' => PetSex::Unknown]);
        $pet = Pet::factory()->dog()->create(['sex' => PetSex::Male]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(5, $result->sex);
    }

    public function test_sex_score_sighting_null_gives_zero(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['sex' => null]);
        $pet = Pet::factory()->dog()->create(['sex' => PetSex::Male]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(0, $result->sex);
    }

    // ──────────────────────────────────────────────
    // COLOR
    // ──────────────────────────────────────────────

    public function test_color_score_exact_match_gives_max(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['color' => 'preto']);
        $pet = Pet::factory()->dog()->create(['primary_color' => 'preto']);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(MatchScoringService::COLOR_MAX, $result->color);
    }

    public function test_color_score_partial_token_overlap(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['color' => 'preto e branco']);
        $pet = Pet::factory()->dog()->create(['primary_color' => 'preto']);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        // 1 intersection / 2 union * 5 = 2.5
        $this->assertEqualsWithDelta(2.5, $result->color, 0.01);
    }

    public function test_color_score_both_null_gives_partial(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['color' => null]);
        $pet = Pet::factory()->dog()->create(['primary_color' => null]);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(3, $result->color);
    }

    public function test_color_score_one_null_gives_zero(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['color' => null]);
        $pet = Pet::factory()->dog()->create(['primary_color' => 'preto']);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(0, $result->color);
    }

    public function test_color_score_no_match_gives_zero(): void
    {
        $sighting = PetSighting::factory()->dog()->create(['color' => 'branco']);
        $pet = Pet::factory()->dog()->create(['primary_color' => 'preto']);

        $result = $this->scorer->calculateScore($sighting, $pet->load('characteristics'), 1000);

        $this->assertEquals(0, $result->color);
    }

    // ──────────────────────────────────────────────
    // CHARACTERISTICS
    // ──────────────────────────────────────────────

    public function test_characteristics_score_both_empty_gives_partial(): void
    {
        $sighting = PetSighting::factory()->dog()->create();
        $pet = Pet::factory()->dog()->create();

        $result = $this->scorer->calculateScore($sighting->load('characteristics'), $pet->load('characteristics'), 1000);

        $this->assertEquals(5, $result->characteristics);
    }

    public function test_characteristics_score_full_overlap_gives_max(): void
    {
        $chars = Characteristic::factory()->count(3)->create();
        $sighting = PetSighting::factory()->dog()->create();
        $sighting->characteristics()->attach($chars->pluck('id'));
        $pet = Pet::factory()->dog()->create();
        $pet->characteristics()->attach($chars->pluck('id'));

        $result = $this->scorer->calculateScore($sighting->load('characteristics'), $pet->load('characteristics'), 1000);

        $this->assertEquals(MatchScoringService::CHARACTERISTICS_MAX, $result->characteristics);
    }

    public function test_characteristics_score_partial_overlap(): void
    {
        $shared = Characteristic::factory()->create();
        $extra = Characteristic::factory()->create();

        $sighting = PetSighting::factory()->dog()->create();
        $sighting->characteristics()->attach([$shared->id, $extra->id]);
        $pet = Pet::factory()->dog()->create();
        $pet->characteristics()->attach([$shared->id]);

        $result = $this->scorer->calculateScore($sighting->load('characteristics'), $pet->load('characteristics'), 1000);

        // 1 intersection / 2 union * 10 = 5
        $this->assertEqualsWithDelta(5, $result->characteristics, 0.01);
    }

    // ──────────────────────────────────────────────
    // COMPOSITION: calculateScore() end-to-end
    // ──────────────────────────────────────────────

    public function test_calculate_score_strong_match(): void
    {
        $breed = Breed::factory()->dog()->create();
        $chars = Characteristic::factory()->count(2)->create();

        $sighting = PetSighting::factory()->dog()->create([
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'color' => 'caramelo',
        ]);
        $sighting->characteristics()->attach($chars->pluck('id'));

        $pet = Pet::factory()->dog()->create([
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'caramelo',
        ]);
        $pet->characteristics()->attach($chars->pluck('id'));

        $result = $this->scorer->calculateScore($sighting->load('characteristics'), $pet->load('characteristics'), 500);

        $this->assertInstanceOf(MatchScoreResult::class, $result);
        $this->assertGreaterThan(MatchScoringService::SCORE_THRESHOLD, $result->total);

        // Verify breakdown sums to total
        $sum = $result->proximity + $result->breed + $result->size + $result->sex + $result->color + $result->characteristics;
        $this->assertEqualsWithDelta($result->total, $sum, 0.01);

        // Strong match: breed=25, size=10, sex=10, color=5, characteristics=10, proximity~34.3
        $this->assertGreaterThan(80, $result->total);
    }

    public function test_calculate_score_explicit_mismatch(): void
    {
        $breed1 = Breed::factory()->dog()->create();
        $breed2 = Breed::factory()->dog()->create();

        $sighting = PetSighting::factory()->dog()->create([
            'breed_id' => $breed1->id,
            'size' => PetSize::Small,
            'sex' => PetSex::Male,
            'color' => 'branco',
        ]);

        $pet = Pet::factory()->dog()->create([
            'breed_id' => $breed2->id,
            'size' => PetSize::Large,
            'sex' => PetSex::Female,
            'primary_color' => 'preto',
        ]);

        $result = $this->scorer->calculateScore($sighting->load('characteristics'), $pet->load('characteristics'), 20000);

        // breed=-15, size=0, sex=-10, color=0, proximity~7, characteristics=5
        $this->assertLessThan(MatchScoringService::SCORE_THRESHOLD, $result->total);

        // Verify breakdown sums to total
        $sum = $result->proximity + $result->breed + $result->size + $result->sex + $result->color + $result->characteristics;
        $this->assertEqualsWithDelta($result->total, $sum, 0.01);
    }

    public function test_calculate_score_incomplete_data(): void
    {
        $sighting = PetSighting::factory()->dog()->create([
            'breed_id' => null,
            'size' => null,
            'sex' => null,
            'color' => null,
        ]);

        $pet = Pet::factory()->dog()->create([
            'primary_color' => null,
        ]);
        // Reset breed to null (afterCreating auto-assigns one)
        $pet->update(['breed_id' => null, 'secondary_breed_id' => null]);
        $pet->refresh();

        $result = $this->scorer->calculateScore($sighting->load('characteristics'), $pet->load('characteristics'), null);

        // proximity=0, breed=5 (both null), size=0 (sighting null), sex=0 (sighting null), color=3 (both null), characteristics=5 (both empty)
        $this->assertEqualsWithDelta(13, $result->total, 0.01);

        // Verify breakdown sums to total
        $sum = $result->proximity + $result->breed + $result->size + $result->sex + $result->color + $result->characteristics;
        $this->assertEqualsWithDelta($result->total, $sum, 0.01);
    }

    // ──────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────

    /**
     * Score with only proximity varying — uses default sighting/pet with matching defaults
     * so non-proximity criteria produce consistent baseline values.
     */
    private function score(?float $distance): MatchScoreResult
    {
        $breed = Breed::factory()->dog()->create();

        $sighting = PetSighting::factory()->dog()->create([
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'color' => 'preto',
        ]);

        $pet = Pet::factory()->dog()->create([
            'breed_id' => $breed->id,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'primary_color' => 'preto',
        ]);

        return $this->scorer->calculateScore($sighting->load('characteristics'), $pet->load('characteristics'), $distance);
    }
}
