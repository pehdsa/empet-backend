<?php

namespace Tests\Unit;

use App\Services\MatchAi\Providers\OpenAiMatchAiProvider;
use App\Support\Matching\MatchAiInput;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiMatchAiProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.match_ai.openai.api_key' => 'test-key',
            'services.match_ai.openai.model' => 'gpt-4o',
            'services.match_ai.timeout' => 15,
        ]);
    }

    public function test_returns_success_with_parsed_score_and_confidence(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'score' => 87,
                            'confidence' => 0.82,
                            'summary' => 'Alta similaridade visual e temporal',
                        ]),
                    ],
                ]],
            ]),
        ]);

        $result = (new OpenAiMatchAiProvider)->evaluate($this->sampleInput());

        $this->assertTrue($result->success);
        $this->assertSame(87.0, $result->score);
        $this->assertSame(0.82, $result->confidence);
        $this->assertSame('Alta similaridade visual e temporal', $result->summary);
        $this->assertSame('openai', $result->provider);
        $this->assertSame('gpt-4o', $result->model);
    }

    public function test_accepts_numeric_strings_in_score_and_confidence(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'score' => '75',
                            'confidence' => '0.5',
                            'summary' => 'x',
                        ]),
                    ],
                ]],
            ]),
        ]);

        $result = (new OpenAiMatchAiProvider)->evaluate($this->sampleInput());

        $this->assertTrue($result->success);
        $this->assertSame(75.0, $result->score);
        $this->assertSame(0.5, $result->confidence);
    }

    public function test_returns_failed_when_api_key_is_empty(): void
    {
        config(['services.match_ai.openai.api_key' => '']);
        Http::fake();

        $result = (new OpenAiMatchAiProvider)->evaluate($this->sampleInput());

        $this->assertFalse($result->success);
        Http::assertNothingSent();
    }

    public function test_returns_failed_when_content_not_json(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Desculpe, nao entendi a tarefa.'],
                ]],
            ]),
        ]);

        $result = (new OpenAiMatchAiProvider)->evaluate($this->sampleInput());

        $this->assertFalse($result->success);
        $this->assertSame('openai', $result->provider);
    }

    public function test_returns_failed_when_score_missing(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['confidence' => 0.9, 'summary' => 'x']),
                    ],
                ]],
            ]),
        ]);

        $result = (new OpenAiMatchAiProvider)->evaluate($this->sampleInput());

        $this->assertFalse($result->success);
    }

    public function test_returns_failed_on_http_error(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'rate limited'], 429),
        ]);

        $result = (new OpenAiMatchAiProvider)->evaluate($this->sampleInput());

        $this->assertFalse($result->success);
    }

    public function test_returns_failed_on_connection_exception(): void
    {
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });

        $result = (new OpenAiMatchAiProvider)->evaluate($this->sampleInput());

        $this->assertFalse($result->success);
    }

    public function test_sends_photo_urls_as_image_url_parts(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['score' => 80, 'confidence' => 0.7, 'summary' => 'ok']),
                    ],
                ]],
            ]),
        ]);

        (new OpenAiMatchAiProvider)->evaluate(new MatchAiInput(
            lostPet: ['species' => 'DOG'],
            sighting: ['species' => 'DOG'],
            lostPetPhotoUrls: ['https://s3.example/pet-1.jpg'],
            sightingPhotoUrls: ['https://s3.example/sighting-1.jpg', 'https://s3.example/sighting-2.jpg'],
            distanceMeters: 1000.0,
            daysSinceSighting: 2,
        ));

        Http::assertSent(function ($request) {
            $body = $request->data();
            $userContent = $body['messages'][1]['content'];
            $imageParts = array_filter($userContent, fn ($p) => $p['type'] === 'image_url');

            return count($imageParts) === 3;
        });
    }

    public function test_sends_bearer_token_and_model(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['score' => 80, 'confidence' => 0.7, 'summary' => 'ok']),
                    ],
                ]],
            ]),
        ]);

        (new OpenAiMatchAiProvider)->evaluate($this->sampleInput());

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-key')
                && $request->data()['model'] === 'gpt-4o'
                && $request->data()['response_format']['type'] === 'json_object';
        });
    }

    private function sampleInput(): MatchAiInput
    {
        return new MatchAiInput(
            lostPet: ['species' => 'DOG', 'breed' => 'Golden'],
            sighting: ['species' => 'DOG', 'breed' => 'Golden'],
            lostPetPhotoUrls: [],
            sightingPhotoUrls: [],
            distanceMeters: 500.0,
            daysSinceSighting: 1,
        );
    }
}
