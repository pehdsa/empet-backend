<?php

namespace Tests\Feature\Pet;

use App\Models\Breed;
use App\Models\Characteristic;
use App\Models\Pet;
use App\Models\PetPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetCrudTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // STORE (POST /api/v1/pets)
    // ──────────────────────────────────────────────

    public function test_store_creates_pet_with_all_fields_and_photos_and_characteristics(): void
    {
        Storage::fake('s3');

        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $breed = Breed::factory()->dog()->create();
        $secondaryBreed = Breed::factory()->dog()->create();
        $characteristics = Characteristic::factory()->count(2)->marking()->create();

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'LARGE',
            'sex' => 'MALE',
            'breed_id' => $breed->id,
            'secondary_breed_id' => $secondaryBreed->id,
            'breed_description' => 'Porte maior que o padrão',
            'primary_color' => 'Caramelo',
            'notes' => 'Muito brincalhão',
            'characteristic_ids' => $characteristics->pluck('id')->toArray(),
            'photos' => [
                UploadedFile::fake()->image('photo1.jpg'),
                UploadedFile::fake()->image('photo2.jpg'),
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'species', 'size', 'sex', 'breed', 'secondaryBreed',
                    'breedDescription', 'primaryColor', 'notes', 'isActive', 'photos',
                    'characteristics', 'createdAt', 'updatedAt',
                ],
            ])
            ->assertJsonPath('data.name', 'Rex')
            ->assertJsonPath('data.species', 'DOG')
            ->assertJsonPath('data.breed.id', $breed->id)
            ->assertJsonPath('data.secondaryBreed.id', $secondaryBreed->id)
            ->assertJsonPath('data.breedDescription', 'Porte maior que o padrão')
            ->assertJsonCount(2, 'data.photos')
            ->assertJsonCount(2, 'data.characteristics');

        $this->assertDatabaseHas('pets', [
            'user_id' => $user->id,
            'name' => 'Rex',
            'breed_id' => $breed->id,
            'secondary_breed_id' => $secondaryBreed->id,
        ]);

        $this->assertDatabaseCount('pet_photos', 2);
    }

    public function test_store_without_photos_works(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Mia',
            'species' => 'CAT',
            'size' => 'SMALL',
            'sex' => 'FEMALE',
        ]);

        $response->assertStatus(201)
            ->assertJsonCount(0, 'data.photos');

        $this->assertDatabaseCount('pet_photos', 0);
    }

    public function test_store_sets_correct_photo_positions(): void
    {
        Storage::fake('s3');

        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
            'photos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ],
        ]);

        $response->assertStatus(201);

        $photos = $response->json('data.photos');
        $this->assertEquals(0, $photos[0]['position']);
        $this->assertEquals(1, $photos[1]['position']);
        $this->assertEquals(2, $photos[2]['position']);
    }

    public function test_store_with_more_than_5_photos_returns_422(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $photos = [];
        for ($i = 0; $i < 6; $i++) {
            $photos[] = UploadedFile::fake()->image("photo{$i}.jpg");
        }

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
            'photos' => $photos,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photos']);
    }

    public function test_store_with_invalid_enum_returns_422(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'BIRD',
            'size' => 'HUGE',
            'sex' => 'OTHER',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['species', 'size', 'sex']);
    }

    public function test_store_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_returns_422_when_required_fields_missing(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'species', 'size', 'sex']);
    }

    public function test_store_with_inactive_characteristic_returns_422(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $inactive = Characteristic::factory()->create(['is_active' => false]);

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
            'characteristic_ids' => [$inactive->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['characteristic_ids.0']);
    }

    public function test_store_with_breed_id_of_wrong_species_returns_422(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $catBreed = Breed::factory()->cat()->create();

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
            'breed_id' => $catBreed->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['breed_id']);
    }

    public function test_store_with_secondary_breed_id_of_wrong_species_returns_422(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $dogBreed = Breed::factory()->dog()->create();
        $catBreed = Breed::factory()->cat()->create();

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
            'breed_id' => $dogBreed->id,
            'secondary_breed_id' => $catBreed->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['secondary_breed_id']);
    }

    public function test_store_with_secondary_breed_id_same_as_breed_id_returns_422(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $breed = Breed::factory()->dog()->create();

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
            'breed_id' => $breed->id,
            'secondary_breed_id' => $breed->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['secondary_breed_id']);
    }

    public function test_store_with_breed_description_only_works(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Vira-lata',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
            'breed_description' => 'Vira-lata com traços de Pinscher',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.breedDescription', 'Vira-lata com traços de Pinscher');
    }

    public function test_store_with_breed_id_and_breed_description_works(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $breed = Breed::factory()->dog()->create();

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
            'breed_id' => $breed->id,
            'breed_description' => 'Porte menor que o padrão',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.breed.id', $breed->id)
            ->assertJsonPath('data.breedDescription', 'Porte menor que o padrão');
    }

    public function test_store_with_all_breed_fields_null_works(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
        ]);

        $response->assertStatus(201);
    }

    public function test_store_with_inactive_breed_returns_422(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user, ['*']);

        $breed = Breed::factory()->dog()->inactive()->create();

        $response = $this->postJson('/api/v1/pets', [
            'name' => 'Rex',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
            'breed_id' => $breed->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['breed_id']);
    }

    // ──────────────────────────────────────────────
    // INDEX (GET /api/v1/pets)
    // ──────────────────────────────────────────────

    public function test_index_client_lists_only_own_pets(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();

        Pet::factory()->count(2)->create(['user_id' => $user->id]);
        Pet::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pets');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_index_client_does_not_see_other_users_pets(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();

        Pet::factory()->count(3)->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pets');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_index_client_does_not_see_soft_deleted_pets(): void
    {
        $user = User::factory()->client()->create();

        Pet::factory()->create(['user_id' => $user->id]);
        Pet::factory()->create(['user_id' => $user->id, 'deleted_at' => now()]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pets');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_index_client_sees_own_inactive_pets(): void
    {
        $user = User::factory()->client()->create();

        Pet::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        Pet::factory()->create(['user_id' => $user->id, 'is_active' => false]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/pets');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_index_admin_lists_all_pets(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->client()->create();
        $user2 = User::factory()->client()->create();

        Pet::factory()->count(2)->create(['user_id' => $user1->id]);
        Pet::factory()->create(['user_id' => $user2->id]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/pets');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_index_admin_filters_by_user_id(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->client()->create();
        $user2 = User::factory()->client()->create();

        Pet::factory()->count(2)->create(['user_id' => $user1->id]);
        Pet::factory()->create(['user_id' => $user2->id]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/pets?user_id='.$user1->id);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/pets');

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    // SHOW (GET /api/v1/pets/{pet})
    // ──────────────────────────────────────────────

    public function test_show_client_sees_own_pet_with_photos_and_characteristics(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        PetPhoto::factory()->create(['pet_id' => $pet->id, 'position' => 0]);
        $characteristic = Characteristic::factory()->create();
        $pet->characteristics()->attach($characteristic);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'species', 'size', 'sex', 'breed', 'secondaryBreed',
                    'breedDescription', 'primaryColor', 'notes', 'isActive', 'photos',
                    'characteristics', 'createdAt', 'updatedAt',
                ],
            ])
            ->assertJsonCount(1, 'data.photos')
            ->assertJsonCount(1, 'data.characteristics');
    }

    public function test_show_returns_breed_as_object(): void
    {
        $user = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create(['name' => 'Labrador']);
        $pet = Pet::factory()->create([
            'user_id' => $user->id,
            'species' => 'DOG',
            'breed_id' => $breed->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.breed.id', $breed->id)
            ->assertJsonPath('data.breed.name', 'Labrador')
            ->assertJsonPath('data.breed.species', 'DOG');
    }

    public function test_show_returns_null_breed_when_pet_has_no_breed(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create([
            'user_id' => $user->id,
        ]);
        $pet->update(['breed_id' => null]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.breed', null);
    }

    public function test_show_photos_ordered_by_position(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        PetPhoto::factory()->create(['pet_id' => $pet->id, 'position' => 2]);
        PetPhoto::factory()->create(['pet_id' => $pet->id, 'position' => 0]);
        PetPhoto::factory()->create(['pet_id' => $pet->id, 'position' => 1]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pets/{$pet->id}");

        $photos = $response->json('data.photos');
        $this->assertEquals(0, $photos[0]['position']);
        $this->assertEquals(1, $photos[1]['position']);
        $this->assertEquals(2, $photos[2]['position']);
    }

    public function test_show_photo_resource_transforms_path_to_url(): void
    {
        Storage::fake('s3');

        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        PetPhoto::factory()->create(['pet_id' => $pet->id, 'path' => 'pets/photos/test.jpg']);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pets/{$pet->id}");

        $photoUrl = $response->json('data.photos.0.url');
        $this->assertNotNull($photoUrl);
        $this->assertStringContainsString('pets/photos/test.jpg', $photoUrl);
    }

    public function test_show_soft_deleted_pet_returns_404(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id, 'deleted_at' => now()]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(404);
    }

    public function test_show_admin_can_see_other_users_pet(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(200);
    }

    public function test_show_client_cannot_see_other_users_pet(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(403);
    }

    public function test_show_returns_401_when_unauthenticated(): void
    {
        $pet = Pet::factory()->create();

        $response = $this->getJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    // UPDATE (PUT /api/v1/pets/{pet})
    // ──────────────────────────────────────────────

    public function test_update_happy_path(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);
        $breed = Breed::factory()->cat()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => 'New Name',
            'species' => 'CAT',
            'size' => 'SMALL',
            'sex' => 'FEMALE',
            'breed_id' => $breed->id,
            'primary_color' => 'White',
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.breed.id', $breed->id);
    }

    public function test_update_switch_to_breed_description_only(): void
    {
        $user = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $pet = Pet::factory()->create([
            'user_id' => $user->id,
            'species' => 'DOG',
            'breed_id' => $breed->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => $pet->name,
            'species' => 'DOG',
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
            'breed_id' => null,
            'breed_description' => 'Mix desconhecido',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.breed', null)
            ->assertJsonPath('data.breedDescription', 'Mix desconhecido');
    }

    public function test_update_clear_secondary_breed(): void
    {
        $user = User::factory()->client()->create();
        $breed = Breed::factory()->dog()->create();
        $secondary = Breed::factory()->dog()->create();
        $pet = Pet::factory()->create([
            'user_id' => $user->id,
            'species' => 'DOG',
            'breed_id' => $breed->id,
            'secondary_breed_id' => $secondary->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => $pet->name,
            'species' => 'DOG',
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
            'breed_id' => $breed->id,
            'secondary_breed_id' => null,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.secondaryBreed', null);

        $this->assertDatabaseHas('pets', [
            'id' => $pet->id,
            'secondary_breed_id' => null,
        ]);
    }

    public function test_update_with_breed_id_of_wrong_species_returns_422(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id, 'species' => 'DOG']);
        $catBreed = Breed::factory()->cat()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => $pet->name,
            'species' => 'DOG',
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
            'breed_id' => $catBreed->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['breed_id']);
    }

    public function test_update_adding_new_photos(): void
    {
        Storage::fake('s3');

        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        PetPhoto::factory()->create(['pet_id' => $pet->id, 'position' => 0]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => $pet->name,
            'species' => $pet->species->value,
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
            'new_photos' => [
                UploadedFile::fake()->image('new1.jpg'),
                UploadedFile::fake()->image('new2.jpg'),
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.photos');
    }

    public function test_update_delete_photos_removes_from_storage_and_reindexes(): void
    {
        Storage::fake('s3');

        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        Storage::disk('s3')->put('pets/photos/first.jpg', 'content');
        $photo1 = PetPhoto::factory()->create(['pet_id' => $pet->id, 'path' => 'pets/photos/first.jpg', 'position' => 0]);
        $photo2 = PetPhoto::factory()->create(['pet_id' => $pet->id, 'position' => 1]);
        $photo3 = PetPhoto::factory()->create(['pet_id' => $pet->id, 'position' => 2]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => $pet->name,
            'species' => $pet->species->value,
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
            'delete_photo_ids' => [$photo1->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.photos');

        Storage::disk('s3')->assertMissing('pets/photos/first.jpg');

        $photos = $response->json('data.photos');
        $this->assertEquals(0, $photos[0]['position']);
        $this->assertEquals(1, $photos[1]['position']);
    }

    public function test_update_delete_photo_of_another_pet_returns_422(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $otherPet = Pet::factory()->create(['user_id' => $user->id]);
        $otherPhoto = PetPhoto::factory()->create(['pet_id' => $otherPet->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => $pet->name,
            'species' => $pet->species->value,
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
            'delete_photo_ids' => [$otherPhoto->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['delete_photo_ids.0']);
    }

    public function test_update_exceeding_photo_limit_returns_422(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        PetPhoto::factory()->count(4)->create(['pet_id' => $pet->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => $pet->name,
            'species' => $pet->species->value,
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
            'new_photos' => [
                UploadedFile::fake()->image('new1.jpg'),
                UploadedFile::fake()->image('new2.jpg'),
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_photos']);
    }

    public function test_update_without_characteristic_ids_keeps_existing(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $characteristics = Characteristic::factory()->count(2)->create();
        $pet->characteristics()->sync($characteristics->pluck('id'));

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => 'Updated',
            'species' => $pet->species->value,
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.characteristics');
    }

    public function test_update_with_empty_characteristic_ids_clears_all(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $characteristics = Characteristic::factory()->count(2)->create();
        $pet->characteristics()->sync($characteristics->pluck('id'));

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => 'Updated',
            'species' => $pet->species->value,
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
            'characteristic_ids' => [],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.characteristics');
    }

    public function test_update_without_photos_keeps_collection_intact(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        PetPhoto::factory()->count(2)->create(['pet_id' => $pet->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => 'Updated',
            'species' => $pet->species->value,
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.photos');
    }

    public function test_update_with_inactive_characteristic_returns_422(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        $inactive = Characteristic::factory()->create(['is_active' => false]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => 'Updated',
            'species' => $pet->species->value,
            'size' => $pet->size->value,
            'sex' => $pet->sex->value,
            'characteristic_ids' => [$inactive->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['characteristic_ids.0']);
    }

    public function test_update_with_invalid_enum_returns_422(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => 'Updated',
            'species' => 'BIRD',
            'size' => 'HUGE',
            'sex' => 'OTHER',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['species', 'size', 'sex']);
    }

    public function test_update_client_cannot_update_other_users_pet(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => 'Hacked',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_admin_cannot_update_other_users_pet(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => 'Admin Edit',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_returns_401_when_unauthenticated(): void
    {
        $pet = Pet::factory()->create();

        $response = $this->putJson("/api/v1/pets/{$pet->id}", [
            'name' => 'Updated',
            'species' => 'DOG',
            'size' => 'MEDIUM',
            'sex' => 'MALE',
        ]);

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    // DESTROY (DELETE /api/v1/pets/{pet})
    // ──────────────────────────────────────────────

    public function test_destroy_soft_deletes_pet(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Pet deleted successfully.');

        $this->assertSoftDeleted('pets', ['id' => $pet->id]);
    }

    public function test_destroy_keeps_photos_in_storage_and_database(): void
    {
        Storage::fake('s3');

        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);
        Storage::disk('s3')->put('pets/photos/keep.jpg', 'content');
        PetPhoto::factory()->create(['pet_id' => $pet->id, 'path' => 'pets/photos/keep.jpg']);

        Sanctum::actingAs($user, ['*']);

        $this->deleteJson("/api/v1/pets/{$pet->id}");

        Storage::disk('s3')->assertExists('pets/photos/keep.jpg');
        $this->assertDatabaseHas('pet_photos', ['pet_id' => $pet->id]);
    }

    public function test_destroy_client_cannot_delete_other_users_pet(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(403);
    }

    public function test_destroy_admin_cannot_delete_other_users_pet(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->deleteJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(403);
    }

    public function test_destroy_returns_401_when_unauthenticated(): void
    {
        $pet = Pet::factory()->create();

        $response = $this->deleteJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    // TOGGLE ACTIVE (PATCH /api/v1/pets/{pet}/toggle-active)
    // ──────────────────────────────────────────────

    public function test_toggle_active_admin_deactivates_pet(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id, 'is_active' => true]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->patchJson("/api/v1/pets/{$pet->id}/toggle-active");

        $response->assertStatus(200)
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('pets', ['id' => $pet->id, 'is_active' => false]);
    }

    public function test_toggle_active_admin_reactivates_pet(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id, 'is_active' => false]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->patchJson("/api/v1/pets/{$pet->id}/toggle-active");

        $response->assertStatus(200)
            ->assertJsonPath('data.isActive', true);
    }

    public function test_toggle_active_returns_pet_resource_with_updated_state(): void
    {
        $admin = User::factory()->admin()->create();
        $pet = Pet::factory()->create(['is_active' => true]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->patchJson("/api/v1/pets/{$pet->id}/toggle-active");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'isActive', 'photos', 'characteristics'],
            ]);
    }

    public function test_toggle_active_soft_deleted_pet_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $pet = Pet::factory()->create(['deleted_at' => now()]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->patchJson("/api/v1/pets/{$pet->id}/toggle-active");

        $response->assertStatus(404);
    }

    public function test_toggle_active_client_cannot_toggle(): void
    {
        $user = User::factory()->client()->create();
        $pet = Pet::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->patchJson("/api/v1/pets/{$pet->id}/toggle-active");

        $response->assertStatus(403);
    }

    public function test_toggle_active_returns_401_when_unauthenticated(): void
    {
        $pet = Pet::factory()->create();

        $response = $this->patchJson("/api/v1/pets/{$pet->id}/toggle-active");

        $response->assertStatus(401);
    }
}
