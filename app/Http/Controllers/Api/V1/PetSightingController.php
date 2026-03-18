<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PetSighting\StorePetSighting;
use App\DTOs\PetSighting\StorePetSightingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\PetSighting\StorePetSightingRequest;
use App\Http\Resources\PetSightingResource;
use App\Models\PetReport;
use App\Models\PetSighting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PetSightingController extends Controller
{
    public function index(Request $request, PetReport $petReport): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', [PetSighting::class, $petReport]);

        $sightings = $petReport->sightings()
            ->withCoordinates()
            ->with(['report', 'user.phones' => fn ($q) => $q->where('is_primary', true)])
            ->orderByDesc('created_at')
            ->paginateFromRequest();

        return PetSightingResource::collection($sightings);
    }

    public function store(StorePetSightingRequest $request, PetReport $petReport, StorePetSighting $action): JsonResponse
    {
        Gate::authorize('create', PetSighting::class);

        $data = new StorePetSightingData(
            reportId: $petReport->id,
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
            addressHint: $request->validated('address_hint'),
            description: $request->validated('description'),
            sightedAt: Carbon::parse($request->validated('sighted_at')),
            sharePhone: (bool) $request->validated('share_phone', false),
        );

        $result = $action->handle($data, $request->user());
        $sighting = $result['sighting'];

        $sighting = PetSighting::query()
            ->withCoordinates()
            ->with(['report', 'user.phones' => fn ($q) => $q->where('is_primary', true)])
            ->find($sighting->id);

        return (new PetSightingResource($sighting))
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }

    public function show(Request $request, PetReport $petReport, PetSighting $petSighting): PetSightingResource
    {
        if ($petSighting->report_id !== $petReport->id) {
            abort(404, 'Sighting not found for this report.');
        }

        Gate::authorize('view', $petSighting);

        $petSighting = PetSighting::query()
            ->withCoordinates()
            ->with(['report', 'user.phones' => fn ($q) => $q->where('is_primary', true)])
            ->find($petSighting->id);

        return new PetSightingResource($petSighting);
    }
}
