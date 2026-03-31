<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Pet\DeletePet;
use App\Actions\Pet\StorePet;
use App\Actions\Pet\UpdatePet;
use App\DTOs\Pet\StorePetData;
use App\DTOs\Pet\UpdatePetData;
use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pet\PetIndexRequest;
use App\Http\Requests\Pet\StorePetRequest;
use App\Http\Requests\Pet\UpdatePetRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\PetResource;
use App\Models\Pet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PetController extends Controller
{
    public function index(PetIndexRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Pet::class);

        $query = $request->user()->role === UserRole::Admin
            ? Pet::query()
            : $request->user()->pets();

        if ($request->user()->role === UserRole::Admin && $request->validated('user_id')) {
            $query->where('user_id', $request->validated('user_id'));
        }

        $pets = $query
            ->with(['photos', 'characteristics', 'breed', 'secondaryBreed', 'activeReport'])
            ->paginateFromRequest();

        return PetResource::collection($pets);
    }

    public function store(StorePetRequest $request, StorePet $action): JsonResponse
    {
        Gate::authorize('create', Pet::class);

        $data = new StorePetData(
            name: $request->validated('name'),
            species: PetSpecies::from($request->validated('species')),
            size: PetSize::from($request->validated('size')),
            sex: PetSex::from($request->validated('sex')),
            breedId: $request->validated('breed_id'),
            secondaryBreedId: $request->validated('secondary_breed_id'),
            breedDescription: $request->validated('breed_description'),
            primaryColor: $request->validated('primary_color'),
            notes: $request->validated('notes'),
            characteristicIds: $request->validated('characteristic_ids', []),
            photos: $request->file('photos', []),
        );

        $pet = $action->handle($data, $request->user());

        return (new PetResource($pet))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Pet $pet): PetResource
    {
        Gate::authorize('view', $pet);

        $pet->load(['photos', 'characteristics', 'breed', 'secondaryBreed', 'activeReport']);

        return new PetResource($pet);
    }

    public function update(UpdatePetRequest $request, Pet $pet, UpdatePet $action): PetResource
    {
        Gate::authorize('update', $pet);

        $data = new UpdatePetData(
            pet: $pet,
            name: $request->validated('name'),
            species: PetSpecies::from($request->validated('species')),
            size: PetSize::from($request->validated('size')),
            sex: PetSex::from($request->validated('sex')),
            breedId: $request->validated('breed_id'),
            secondaryBreedId: $request->validated('secondary_breed_id'),
            breedDescription: $request->validated('breed_description'),
            primaryColor: $request->validated('primary_color'),
            notes: $request->validated('notes'),
            characteristicIds: $request->has('characteristic_ids') ? $request->validated('characteristic_ids') : null,
            newPhotos: $request->hasFile('new_photos') ? $request->file('new_photos') : null,
            deletePhotoIds: $request->has('delete_photo_ids') ? $request->validated('delete_photo_ids') : null,
        );

        $pet = $action->handle($data);

        return new PetResource($pet);
    }

    public function destroy(Pet $pet, DeletePet $action): JsonResponse
    {
        Gate::authorize('delete', $pet);

        $action->handle($pet);

        return (new MessageResource('Pet deleted successfully.'))
            ->response()
            ->setStatusCode(200);
    }

    public function toggleActive(Pet $pet): PetResource
    {
        Gate::authorize('toggleActive', $pet);

        $pet->update(['is_active' => ! $pet->is_active]);

        $pet->load(['photos', 'characteristics', 'breed', 'secondaryBreed', 'activeReport']);

        return new PetResource($pet);
    }
}
