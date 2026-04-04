<?php

namespace Tests\Unit;

use App\Services\MatchAi\Providers\NullMatchAiProvider;
use App\Support\Matching\MatchAiInput;
use PHPUnit\Framework\TestCase;

class NullMatchAiProviderTest extends TestCase
{
    public function test_returns_failed_result_without_side_effects(): void
    {
        $provider = new NullMatchAiProvider;

        $result = $provider->evaluate(new MatchAiInput(
            lostPet: [],
            sighting: [],
            lostPetPhotoUrls: [],
            sightingPhotoUrls: [],
            distanceMeters: 1000.0,
            daysSinceSighting: 0,
        ));

        $this->assertFalse($result->success);
        $this->assertNull($result->score);
        $this->assertNull($result->confidence);
        $this->assertNull($result->summary);
        $this->assertSame('null', $result->provider);
        $this->assertSame('null', $result->model);
    }
}
