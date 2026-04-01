<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PetReportSighting\StorePetReportSighting;
use App\DTOs\PetReportSighting\StorePetReportSightingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\PetReportSighting\StorePetReportSightingRequest;
use App\Http\Resources\PetReportSightingResource;
use App\Models\PetReport;
use App\Models\PetReportSighting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PetReportSightingController extends Controller
{
    public function index(Request $request, PetReport $petReport): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', [PetReportSighting::class, $petReport]);

        $sightings = $petReport->reportSightings()
            ->withCoordinates()
            ->with(['report', 'user.phones' => fn ($q) => $q->where('is_primary', true)])
            ->orderByDesc('created_at')
            ->paginateFromRequest();

        return PetReportSightingResource::collection($sightings);
    }

    public function store(StorePetReportSightingRequest $request, PetReport $petReport, StorePetReportSighting $action): JsonResponse
    {
        Gate::authorize('create', PetReportSighting::class);

        $data = new StorePetReportSightingData(
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

        $sighting = PetReportSighting::query()
            ->withCoordinates()
            ->with(['report', 'user.phones' => fn ($q) => $q->where('is_primary', true)])
            ->find($sighting->id);

        return (new PetReportSightingResource($sighting))
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }

    public function show(Request $request, PetReport $petReport, PetReportSighting $petReportSighting): PetReportSightingResource
    {
        if ($petReportSighting->report_id !== $petReport->id) {
            abort(404, 'Sighting not found for this report.');
        }

        Gate::authorize('view', $petReportSighting);

        $petReportSighting = PetReportSighting::query()
            ->withCoordinates()
            ->with(['report', 'user.phones' => fn ($q) => $q->where('is_primary', true)])
            ->find($petReportSighting->id);

        return new PetReportSightingResource($petReportSighting);
    }
}
