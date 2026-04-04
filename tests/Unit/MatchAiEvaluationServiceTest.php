<?php

namespace Tests\Unit;

use App\Contracts\MatchAiProvider;
use App\Models\Pet;
use App\Models\PetSighting;
use App\Services\MatchAiEvaluationService;
use App\Services\MatchAiPayloadBuilder;
use App\Support\Matching\MatchAiInput;
use App\Support\Matching\MatchAiResult;
use Tests\TestCase;

class MatchAiEvaluationServiceTest extends TestCase
{
    // ──────────────────────────────────────────────
    // calculateAdjustment
    // ──────────────────────────────────────────────

    public function test_adjustment_is_zero_when_ai_score_is_null(): void
    {
        $this->assertSame(0, MatchAiEvaluationService::calculateAdjustment(null));
    }

    public function test_adjustment_is_plus_ten_for_ai_score_80_or_above(): void
    {
        $this->assertSame(10, MatchAiEvaluationService::calculateAdjustment(80.0));
        $this->assertSame(10, MatchAiEvaluationService::calculateAdjustment(95.0));
        $this->assertSame(10, MatchAiEvaluationService::calculateAdjustment(100.0));
    }

    public function test_adjustment_is_plus_five_for_ai_score_60_to_79(): void
    {
        $this->assertSame(5, MatchAiEvaluationService::calculateAdjustment(60.0));
        $this->assertSame(5, MatchAiEvaluationService::calculateAdjustment(79.99));
    }

    public function test_adjustment_is_zero_for_ai_score_40_to_59(): void
    {
        $this->assertSame(0, MatchAiEvaluationService::calculateAdjustment(40.0));
        $this->assertSame(0, MatchAiEvaluationService::calculateAdjustment(59.99));
    }

    public function test_adjustment_is_minus_ten_for_ai_score_below_40(): void
    {
        $this->assertSame(-10, MatchAiEvaluationService::calculateAdjustment(39.99));
        $this->assertSame(-10, MatchAiEvaluationService::calculateAdjustment(0.0));
    }

    public function test_adjustment_is_zero_when_confidence_below_threshold_even_with_high_score(): void
    {
        $this->assertSame(0, MatchAiEvaluationService::calculateAdjustment(85.0, 0.3));
        $this->assertSame(0, MatchAiEvaluationService::calculateAdjustment(99.0, 0.1));
    }

    public function test_adjustment_applies_normally_when_confidence_above_threshold(): void
    {
        $this->assertSame(10, MatchAiEvaluationService::calculateAdjustment(85.0, 0.4));
        $this->assertSame(10, MatchAiEvaluationService::calculateAdjustment(85.0, 0.9));
    }

    public function test_adjustment_applies_normally_when_confidence_is_null(): void
    {
        $this->assertSame(10, MatchAiEvaluationService::calculateAdjustment(85.0, null));
        $this->assertSame(-10, MatchAiEvaluationService::calculateAdjustment(20.0, null));
    }

    // ──────────────────────────────────────────────
    // calculateFinalScore
    // ──────────────────────────────────────────────

    public function test_final_score_clamps_to_zero_when_below_zero(): void
    {
        $this->assertSame(0.0, MatchAiEvaluationService::calculateFinalScore(5.0, 10.0));
    }

    public function test_final_score_clamps_to_hundred_when_above_hundred(): void
    {
        $this->assertSame(100.0, MatchAiEvaluationService::calculateFinalScore(95.0, 90.0));
    }

    public function test_final_score_rounds_to_two_decimals(): void
    {
        $this->assertSame(55.57, MatchAiEvaluationService::calculateFinalScore(50.57, 70.0));
    }

    public function test_final_score_equals_base_when_ai_score_is_null(): void
    {
        $this->assertSame(75.0, MatchAiEvaluationService::calculateFinalScore(75.0, null));
    }

    public function test_final_score_equals_base_when_confidence_low(): void
    {
        $this->assertSame(50.0, MatchAiEvaluationService::calculateFinalScore(50.0, 90.0, 0.2));
    }

    // ──────────────────────────────────────────────
    // evaluate
    // ──────────────────────────────────────────────

    public function test_evaluate_returns_failed_when_score_out_of_range(): void
    {
        $service = $this->makeService($this->fakeProvider(new MatchAiResult(
            success: true,
            score: 150.0,
            confidence: 0.9,
            summary: 'x',
            provider: 'fake',
            model: 'test',
        )));

        $result = $service->evaluate(...$this->fakeModels());

        $this->assertFalse($result->success);
        $this->assertNull($result->score);
    }

    public function test_evaluate_returns_failed_when_score_null_but_success_true(): void
    {
        $service = $this->makeService($this->fakeProvider(new MatchAiResult(
            success: true,
            score: null,
            confidence: 0.9,
            summary: 'x',
            provider: 'fake',
            model: 'test',
        )));

        $result = $service->evaluate(...$this->fakeModels());

        $this->assertFalse($result->success);
    }

    public function test_evaluate_returns_failed_when_confidence_out_of_range(): void
    {
        $service = $this->makeService($this->fakeProvider(new MatchAiResult(
            success: true,
            score: 80.0,
            confidence: 1.7,
            summary: 'x',
            provider: 'fake',
            model: 'test',
        )));

        $result = $service->evaluate(...$this->fakeModels());

        $this->assertFalse($result->success);
    }

    public function test_evaluate_accepts_null_confidence(): void
    {
        $service = $this->makeService($this->fakeProvider(new MatchAiResult(
            success: true,
            score: 80.0,
            confidence: null,
            summary: 'x',
            provider: 'fake',
            model: 'test',
        )));

        $result = $service->evaluate(...$this->fakeModels());

        $this->assertTrue($result->success);
        $this->assertNull($result->confidence);
    }

    public function test_evaluate_returns_failed_when_provider_throws(): void
    {
        $service = $this->makeService(new class implements MatchAiProvider
        {
            public function evaluate(MatchAiInput $input): MatchAiResult
            {
                throw new \RuntimeException('boom');
            }
        });

        $result = $service->evaluate(...$this->fakeModels());

        $this->assertFalse($result->success);
    }

    public function test_evaluate_normalizes_summary_with_squish_and_limit(): void
    {
        $messySummary = "  Forte   semelhanca\n\nvisual   \t e porte compativel  ";
        $service = $this->makeService($this->fakeProvider(new MatchAiResult(
            success: true,
            score: 80.0,
            confidence: 0.9,
            summary: $messySummary,
            provider: 'fake',
            model: 'test',
        )));

        $result = $service->evaluate(...$this->fakeModels());

        $this->assertSame('Forte semelhanca visual e porte compativel', $result->summary);
    }

    public function test_evaluate_truncates_long_summary_to_255(): void
    {
        $long = str_repeat('a', 400);
        $service = $this->makeService($this->fakeProvider(new MatchAiResult(
            success: true,
            score: 80.0,
            confidence: 0.9,
            summary: $long,
            provider: 'fake',
            model: 'test',
        )));

        $result = $service->evaluate(...$this->fakeModels());

        $this->assertSame(255, strlen((string) $result->summary));
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function makeService(MatchAiProvider $provider): MatchAiEvaluationService
    {
        $builder = new class extends MatchAiPayloadBuilder
        {
            public function build(Pet $lostPet, PetSighting $sighting, float $distanceMeters): MatchAiInput
            {
                return new MatchAiInput(
                    lostPet: [],
                    sighting: [],
                    lostPetPhotoUrls: [],
                    sightingPhotoUrls: [],
                    distanceMeters: $distanceMeters,
                    daysSinceSighting: 0,
                );
            }
        };

        return new MatchAiEvaluationService($provider, $builder);
    }

    private function fakeProvider(MatchAiResult $result): MatchAiProvider
    {
        return new class($result) implements MatchAiProvider
        {
            public function __construct(private readonly MatchAiResult $result) {}

            public function evaluate(MatchAiInput $input): MatchAiResult
            {
                return $this->result;
            }
        };
    }

    /**
     * @return array{0: Pet, 1: PetSighting, 2: float}
     */
    private function fakeModels(): array
    {
        return [new Pet, new PetSighting, 1000.0];
    }
}
