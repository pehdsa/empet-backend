<?php

namespace Tests\Feature\Jobs;

use App\Contracts\MatchAiProvider;
use App\Enums\PetMatchStatus;
use App\Jobs\ProcessMatchAiEvaluation;
use App\Models\Pet;
use App\Models\PetMatch;
use App\Models\PetReport;
use App\Models\PetSighting;
use App\Services\MatchAiEvaluationService;
use App\Support\Matching\MatchAiInput;
use App\Support\Matching\MatchAiResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessMatchAiEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
    }

    public function test_success_updates_match_with_ai_fields_and_final_score(): void
    {
        $this->bindProvider($this->fakeProvider(new MatchAiResult(
            success: true,
            score: 90.0,
            confidence: 0.85,
            summary: 'Forte semelhanca visual',
            provider: 'fake',
            model: 'test',
        )));

        $match = $this->createPendingMatch(baseScore: 70.0);

        (new ProcessMatchAiEvaluation($match))->handle(app(MatchAiEvaluationService::class));

        $match->refresh();

        $this->assertSame('SUCCESS', $match->ai_status);
        $this->assertSame('90.00', $match->ai_score);
        $this->assertSame('0.850', $match->ai_confidence);
        $this->assertSame('fake', $match->ai_provider);
        $this->assertSame('test', $match->ai_model);
        $this->assertSame('Forte semelhanca visual', $match->ai_summary);
        $this->assertNotNull($match->ai_evaluated_at);
        // base_score=70 + adjustment=+10 (score>=80, confidence>=0.4) = 80
        $this->assertSame('80.00', $match->final_score);
    }

    public function test_failure_marks_as_failed_and_keeps_final_score_as_base_score(): void
    {
        $this->bindProvider($this->fakeProvider(MatchAiResult::failed('fake', 'test')));

        $match = $this->createPendingMatch(baseScore: 65.0);

        (new ProcessMatchAiEvaluation($match))->handle(app(MatchAiEvaluationService::class));

        $match->refresh();

        $this->assertSame('FAILED', $match->ai_status);
        $this->assertNull($match->ai_score);
        $this->assertSame('fake', $match->ai_provider);
        $this->assertSame('test', $match->ai_model);
        $this->assertNotNull($match->ai_evaluated_at);
        $this->assertSame('65.00', $match->final_score);
        $this->assertSame('65.00', $match->base_score);
    }

    public function test_early_return_when_status_not_pending(): void
    {
        $this->bindProvider($this->spyProvider($calls));

        $match = $this->createPendingMatch();
        $match->update(['status' => PetMatchStatus::Confirmed]);

        (new ProcessMatchAiEvaluation($match))->handle(app(MatchAiEvaluationService::class));

        $match->refresh();
        $this->assertNull($match->ai_status);
        $this->assertSame(0, $calls[0]);
    }

    public function test_early_return_when_ai_status_already_set(): void
    {
        $this->bindProvider($this->spyProvider($calls));

        $match = $this->createPendingMatch();
        $match->update(['ai_status' => 'SUCCESS']);

        (new ProcessMatchAiEvaluation($match))->handle(app(MatchAiEvaluationService::class));

        $this->assertSame(0, $calls[0]);
    }

    public function test_early_return_when_pet_is_null(): void
    {
        $this->bindProvider($this->spyProvider($calls));

        $match = $this->createPendingMatch();
        $match->report->pet->forceDelete();
        $match->report->update(['pet_id' => null]);

        (new ProcessMatchAiEvaluation($match))->handle(app(MatchAiEvaluationService::class));

        $match->refresh();
        $this->assertNull($match->ai_status);
        $this->assertSame(0, $calls[0]);
    }

    public function test_success_applies_confidence_guard_and_zeros_adjustment_when_low_confidence(): void
    {
        $this->bindProvider($this->fakeProvider(new MatchAiResult(
            success: true,
            score: 95.0,
            confidence: 0.2,
            summary: 's',
            provider: 'fake',
            model: 'test',
        )));

        $match = $this->createPendingMatch(baseScore: 50.0);

        (new ProcessMatchAiEvaluation($match))->handle(app(MatchAiEvaluationService::class));

        $match->refresh();

        $this->assertSame('SUCCESS', $match->ai_status);
        // confidence < 0.4 → adjustment = 0 → final_score = base_score
        $this->assertSame('50.00', $match->final_score);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function createPendingMatch(float $baseScore = 60.0): PetMatch
    {
        $pet = Pet::factory()->create();
        $report = PetReport::factory()->create(['pet_id' => $pet->id, 'user_id' => $pet->user_id]);
        $sighting = PetSighting::factory()->create(['species' => $pet->species]);

        return PetMatch::factory()->create([
            'report_id' => $report->id,
            'sighting_id' => $sighting->id,
            'base_score' => $baseScore,
            'final_score' => $baseScore,
            'distance_meters' => 1000,
            'status' => PetMatchStatus::Pending,
        ]);
    }

    private function bindProvider(MatchAiProvider $provider): void
    {
        $this->app->instance(MatchAiProvider::class, $provider);
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
     * @param  array<int, int>|null  $calls  Passado por referencia, indice 0 incrementa a cada call
     */
    private function spyProvider(?array &$calls): MatchAiProvider
    {
        $calls = [0];

        return new class($calls) implements MatchAiProvider
        {
            public function __construct(private array &$calls) {}

            public function evaluate(MatchAiInput $input): MatchAiResult
            {
                $this->calls[0]++;

                return MatchAiResult::failed('spy', 'spy');
            }
        };
    }
}
