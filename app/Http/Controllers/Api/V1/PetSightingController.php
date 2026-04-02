<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PetSighting\ClaimPetSighting;
use App\Actions\PetSighting\DestroyPetSighting;
use App\Actions\PetSighting\StorePetSighting;
use App\DTOs\PetSighting\StorePetSightingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\PetSighting\ListPetSightingRequest;
use App\Http\Requests\PetSighting\PetSightingIndexRequest;
use App\Http\Requests\PetSighting\StorePetSightingRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\PetSightingClaimResource;
use App\Http\Resources\PetSightingResource;
use App\Models\PetSighting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PetSightingController extends Controller
{
    public function my(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->petSightings()
            ->withCoordinates()
            ->with(['photos', 'breed', 'characteristics'])
            ->orderByDesc('created_at');

        return PetSightingResource::collection($query->paginateFromRequest());
    }

    public function index(PetSightingIndexRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', PetSighting::class);

        $data = $request->validated();
        $lat = $data['latitude'];
        $lng = $data['longitude'];

        $query = PetSighting::query()
            ->withCoordinates()
            ->selectRaw(
                'ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_meters',
                [$lng, $lat]
            );

        if (! empty($data['species'])) {
            $query->where('species', $data['species']);
        }

        if (! empty($data['size'])) {
            $query->where('size', $data['size']);
        }

        $query->orderByRaw(
            'ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) ASC',
            [$lng, $lat]
        );
        $query->orderBy('id');

        $sightings = $query
            ->with(['photos', 'breed', 'characteristics', 'user:id,name,avatar_url'])
            ->paginateFromRequest();

        return PetSightingResource::collection($sightings);
    }

    public function map(ListPetSightingRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', PetSighting::class);

        $data = $request->validated();
        $lat = $data['latitude'];
        $lng = $data['longitude'];
        $radiusMeters = ($data['radius_km'] ?? 10) * 1000;

        $query = PetSighting::query()
            ->withCoordinates()
            ->whereRaw(
                'ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$lng, $lat, $radiusMeters]
            );

        if (! empty($data['species'])) {
            $query->where('species', $data['species']);
        }

        if (! empty($data['size'])) {
            $query->where('size', $data['size']);
        }

        $sightings = $query
            ->with(['photos', 'breed', 'characteristics', 'user:id,name,avatar_url'])
            ->limit(500)
            ->get();

        return PetSightingResource::collection($sightings);
    }

    public function store(StorePetSightingRequest $request, StorePetSighting $action): JsonResponse
    {
        Gate::authorize('create', PetSighting::class);

        $data = new StorePetSightingData(
            title: $request->validated('title'),
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
            sightedAt: Carbon::parse($request->validated('sighted_at')),
            species: $request->validated('species'),
            size: $request->validated('size'),
            sex: $request->validated('sex'),
            color: $request->validated('color'),
            breedId: $request->validated('breed_id'),
            addressHint: $request->validated('address_hint'),
            description: $request->validated('description'),
            sharePhone: (bool) $request->validated('share_phone', false),
            photos: $request->file('photos', []),
            characteristicIds: $request->validated('characteristic_ids', []),
        );

        $sighting = $action->handle($data, $request->user());

        $sighting = PetSighting::query()
            ->withCoordinates()
            ->with(['photos', 'breed', 'characteristics', 'user:id,name,avatar_url'])
            ->find($sighting->id);

        return (new PetSightingResource($sighting))
            ->response()
            ->setStatusCode(201);
    }

    public function show(PetSighting $petSighting): PetSightingResource
    {
        Gate::authorize('view', $petSighting);

        $petSighting = PetSighting::query()
            ->withCoordinates()
            ->with(['photos', 'breed', 'characteristics', 'user:id,name,avatar_url'])
            ->find($petSighting->id);

        return new PetSightingResource($petSighting);
    }

    public function claim(PetSighting $petSighting, ClaimPetSighting $action): JsonResponse
    {
        Gate::authorize('claim', $petSighting);

        $claim = $action->handle($petSighting, request()->user());

        return (new PetSightingClaimResource($claim->load('sighting.user.phones')))
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(PetSighting $petSighting, DestroyPetSighting $action): MessageResource
    {
        Gate::authorize('delete', $petSighting);

        $action->handle($petSighting);

        return new MessageResource('Sighting deleted successfully.');
    }
}
