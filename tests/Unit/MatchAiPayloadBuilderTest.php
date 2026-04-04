<?php

namespace Tests\Unit;

use App\Models\Breed;
use App\Models\Characteristic;
use App\Models\Pet;
use App\Models\PetPhoto;
use App\Models\PetSighting;
use App\Models\PetSightingPhoto;
use App\Services\MatchAiPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MatchAiPayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    private MatchAiPayloadBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        config(['services.match_ai.max_photos' => 3]);

        $this->builder = new MatchAiPayloadBuilder;
    }

    public function test_builds_payload_with_breed_name_not_id(): void
    {
        $breed = Breed::factory()->create(['name' => 'Golden Retriever']);
        $pet = Pet::factory()->create(['breed_id' => $breed->id]);
        $sightingBreed = Breed::factory()->create(['name' => 'Labrador']);
        $sighting = PetSighting::factory()->create(['breed_id' => $sightingBreed->id]);

        $input = $this->builder->build(
            $pet->load(['breed', 'characteristics', 'photos']),
            $sighting->load(['breed', 'characteristics', 'photos']),
            1500.0,
        );

        $this->assertSame('Golden Retriever', $input->lostPet['breed']);
        $this->assertSame('Labrador', $input->sighting['breed']);
    }

    public function test_extracts_characteristic_names_not_ids(): void
    {
        $pet = Pet::factory()->create();
        $c1 = Characteristic::factory()->create(['name' => 'Friendly']);
        $c2 = Characteristic::factory()->create(['name' => 'Neutered']);
        $pet->characteristics()->attach([$c1->id, $c2->id]);

        $sighting = PetSighting::factory()->create();

        $input = $this->builder->build(
            $pet->load(['breed', 'characteristics', 'photos']),
            $sighting->load(['breed', 'characteristics', 'photos']),
            1000.0,
        );

        $this->assertContains('Friendly', $input->lostPet['characteristics']);
        $this->assertContains('Neutered', $input->lostPet['characteristics']);
    }

    public function test_truncates_notes_and_description_to_200_chars(): void
    {
        $longText = str_repeat('a', 400);
        $pet = Pet::factory()->create(['notes' => $longText]);
        $sighting = PetSighting::factory()->create(['description' => $longText]);

        $input = $this->builder->build(
            $pet->load(['breed', 'characteristics', 'photos']),
            $sighting->load(['breed', 'characteristics', 'photos']),
            1000.0,
        );

        $this->assertSame(200, strlen((string) $input->lostPet['notes']));
        $this->assertSame(200, strlen((string) $input->sighting['description']));
    }

    public function test_limits_photos_to_config_max(): void
    {
        config(['services.match_ai.max_photos' => 2]);

        $pet = Pet::factory()->create();
        PetPhoto::factory()->count(5)->create(['pet_id' => $pet->id]);
        $sighting = PetSighting::factory()->create();
        PetSightingPhoto::factory()->count(4)->create(['pet_sighting_id' => $sighting->id]);

        $input = $this->builder->build(
            $pet->load(['breed', 'characteristics', 'photos']),
            $sighting->load(['breed', 'characteristics', 'photos']),
            1000.0,
        );

        $this->assertCount(2, $input->lostPetPhotoUrls);
        $this->assertCount(2, $input->sightingPhotoUrls);
    }

    public function test_works_with_empty_photos(): void
    {
        $pet = Pet::factory()->create();
        $sighting = PetSighting::factory()->create();

        $input = $this->builder->build(
            $pet->load(['breed', 'characteristics', 'photos']),
            $sighting->load(['breed', 'characteristics', 'photos']),
            1000.0,
        );

        $this->assertSame([], $input->lostPetPhotoUrls);
        $this->assertSame([], $input->sightingPhotoUrls);
    }

    public function test_includes_distance_and_days_since_sighting(): void
    {
        $pet = Pet::factory()->create();
        $sighting = PetSighting::factory()->create([
            'sighted_at' => now()->subDays(5),
        ]);

        $input = $this->builder->build(
            $pet->load(['breed', 'characteristics', 'photos']),
            $sighting->load(['breed', 'characteristics', 'photos']),
            2500.0,
        );

        $this->assertSame(2500.0, $input->distanceMeters);
        $this->assertSame(5, $input->daysSinceSighting);
    }

    public function test_returns_empty_photos_when_max_is_zero(): void
    {
        config(['services.match_ai.max_photos' => 0]);

        $pet = Pet::factory()->create();
        PetPhoto::factory()->count(3)->create(['pet_id' => $pet->id]);
        $sighting = PetSighting::factory()->create();

        $input = $this->builder->build(
            $pet->load(['breed', 'characteristics', 'photos']),
            $sighting->load(['breed', 'characteristics', 'photos']),
            1000.0,
        );

        $this->assertSame([], $input->lostPetPhotoUrls);
    }
}
