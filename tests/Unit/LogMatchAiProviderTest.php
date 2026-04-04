<?php

namespace Tests\Unit;

use App\Services\MatchAi\Providers\LogMatchAiProvider;
use App\Support\Matching\MatchAiInput;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogMatchAiProviderTest extends TestCase
{
    public function test_returns_configured_fixed_score_and_confidence(): void
    {
        config([
            'services.match_ai.log.fixed_score' => 88.0,
            'services.match_ai.log.fixed_confidence' => 0.75,
        ]);

        Log::spy();

        $provider = new LogMatchAiProvider;

        $result = $provider->evaluate(new MatchAiInput(
            lostPet: ['species' => 'DOG'],
            sighting: ['species' => 'DOG'],
            lostPetPhotoUrls: [],
            sightingPhotoUrls: [],
            distanceMeters: 500.0,
            daysSinceSighting: 2,
        ));

        $this->assertTrue($result->success);
        $this->assertSame(88.0, $result->score);
        $this->assertSame(0.75, $result->confidence);
        $this->assertSame('log', $result->provider);
        $this->assertSame('log', $result->model);
    }

    public function test_logs_input_details_on_call(): void
    {
        Log::spy();

        (new LogMatchAiProvider)->evaluate(new MatchAiInput(
            lostPet: [],
            sighting: [],
            lostPetPhotoUrls: ['url-1', 'url-2'],
            sightingPhotoUrls: ['url-3'],
            distanceMeters: 1000.0,
            daysSinceSighting: 3,
        ));

        Log::shouldHaveReceived('info')
            ->with('match_ai_log_provider_called', \Mockery::on(function (array $context) {
                return $context['lost_pet_photo_count'] === 2
                    && $context['sighting_photo_count'] === 1
                    && $context['distance_meters'] === 1000.0
                    && $context['days_since_sighting'] === 3;
            }))
            ->once();
    }
}
