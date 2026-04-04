<?php

namespace App\Services\MatchAi\Providers;

use App\Contracts\MatchAiProvider;
use App\Support\Matching\MatchAiInput;
use App\Support\Matching\MatchAiResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiMatchAiProvider implements MatchAiProvider
{
    private const PROVIDER = 'openai';

    public function evaluate(MatchAiInput $input): MatchAiResult
    {
        $model = (string) config('services.match_ai.openai.model', 'gpt-4o');
        $apiKey = (string) config('services.match_ai.openai.api_key', '');
        $baseUrl = rtrim((string) config('services.match_ai.openai.base_url', 'https://api.openai.com/v1'), '/');
        $timeout = (int) config('services.match_ai.timeout', 15);

        if ($apiKey === '') {
            return MatchAiResult::failed(self::PROVIDER, $model);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->systemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->userContent($input),
                        ],
                    ],
                ])
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            Log::warning('match_ai_openai_http_error', [
                'error' => $e->getMessage(),
            ]);

            return MatchAiResult::failed(self::PROVIDER, $model);
        } catch (Throwable $e) {
            Log::warning('match_ai_openai_unexpected_error', [
                'error' => $e->getMessage(),
            ]);

            return MatchAiResult::failed(self::PROVIDER, $model);
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || $content === '') {
            return MatchAiResult::failed(self::PROVIDER, $model);
        }

        $parsed = json_decode($content, associative: true);
        if (! is_array($parsed)) {
            return MatchAiResult::failed(self::PROVIDER, $model);
        }

        $score = $this->toNullableFloat($parsed['score'] ?? null);
        if ($score === null) {
            return MatchAiResult::failed(self::PROVIDER, $model);
        }

        $confidence = $this->toNullableFloat($parsed['confidence'] ?? null);
        $summary = isset($parsed['summary']) && is_string($parsed['summary']) ? $parsed['summary'] : null;

        return new MatchAiResult(
            success: true,
            score: $score,
            confidence: $confidence,
            summary: $summary,
            provider: self::PROVIDER,
            model: $model,
            rawResponse: $content,
        );
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Voce e um assistente que avalia se dois registros descrevem o mesmo animal.

Compare o pet perdido com o avistamento. Considere:
- Similaridade visual (fotos, se disponiveis)
- Consistencia de caracteristicas fisicas (raca, cor, tamanho, sexo)
- Divergencias explicaveis (ex: observador leigo pode errar raca)
- Proximidade geografica e temporal

Retorne APENAS um JSON valido com as chaves:
{
  "score": <numero 0-100>,
  "confidence": <numero 0.0-1.0>,
  "summary": "<explicacao curta em portugues, max 100 chars>"
}
PROMPT;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function userContent(MatchAiInput $input): array
    {
        $text = "Pet perdido:\n".json_encode($input->lostPet, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ."\n\nAvistamento:\n".json_encode($input->sighting, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ."\n\nDistancia: {$input->distanceMeters} metros"
            ."\nDias desde o avistamento: {$input->daysSinceSighting}";

        $content = [
            ['type' => 'text', 'text' => $text],
        ];

        foreach ($input->lostPetPhotoUrls as $url) {
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $url]];
        }

        foreach ($input->sightingPhotoUrls as $url) {
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $url]];
        }

        return $content;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
