<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PetReport\CancelPetReport;
use App\Actions\PetReport\ConfirmMatch;
use App\Actions\PetReport\DismissMatch;
use App\Actions\PetReport\MarkPetReportFound;
use App\Actions\PetReport\StorePetReport;
use App\Actions\PetReport\UpdatePetReport;
use App\DTOs\PetReport\StorePetReportData;
use App\DTOs\PetReport\UpdatePetReportData;
use App\Enums\PetReportStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\PetReport\PetReportFoundRequest;
use App\Http\Requests\PetReport\PetReportIndexRequest;
use App\Http\Requests\PetReport\PetReportLostMapRequest;
use App\Http\Requests\PetReport\PetReportLostRequest;
use App\Http\Requests\PetReport\StorePetReportRequest;
use App\Http\Requests\PetReport\UpdatePetReportRequest;
use App\Http\Resources\PetMatchResource;
use App\Http\Resources\PetReportResource;
use App\Models\PetMatch;
use App\Models\PetReport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PetReportController extends Controller
{
    public function index(PetReportIndexRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', PetReport::class);

        $data = $request->validated();

        $query = $request->user()->role === UserRole::Admin
            ? PetReport::query()
            : $request->user()->petReports();

        $query->withCoordinates();

        if (! empty($data['pet_id'])) {
            $query->where('pet_id', $data['pet_id']);
        }

        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (isset($data['latitude'], $data['longitude'])) {
            $lat = $data['latitude'];
            $lng = $data['longitude'];
            $radiusKm = $data['radius_km'] ?? 10;
            $radiusMeters = $radiusKm * 1000;

            $query->whereRaw(
                'ST_DWithin(location, ST_MakePoint(?, ?)::geography, ?)',
                [$lng, $lat, $radiusMeters]
            );

            $query->orderByRaw(
                'ST_Distance(location, ST_MakePoint(?, ?)::geography) ASC',
                [$lng, $lat]
            );
        }

        if (! empty($data['species']) || ! empty($data['size'])) {
            $query->whereHas('pet', function ($q) use ($data) {
                if (! empty($data['species'])) {
                    $q->where('species', $data['species']);
                }
                if (! empty($data['size'])) {
                    $q->where('size', $data['size']);
                }
            });
        }

        $with = ['pet.photos'];
        if ($request->user()->role === UserRole::Admin) {
            $with[] = 'user';
        }

        $skipPagination = ($data['paginate'] ?? null) === 'false';

        if ($skipPagination) {
            $reports = $query->with($with)->limit(500)->get();

            return PetReportResource::collection($reports);
        }

        $reports = $query->with($with)->paginateFromRequest();

        return PetReportResource::collection($reports);
    }

    public function lost(PetReportLostRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewLost', PetReport::class);

        $data = $request->validated();
        $lat = $data['latitude'];
        $lng = $data['longitude'];

        $query = PetReport::query()
            ->where('status', PetReportStatus::Lost)
            ->where('is_active', true)
            ->whereHas('pet', fn ($q) => $q->whereNull('deleted_at'))
            ->whereNotNull('location')
            ->withCoordinates();

        $query->selectRaw(
            'ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_meters',
            [$lng, $lat]
        );

        if (! empty($data['species']) || ! empty($data['size'])) {
            $query->whereHas('pet', function ($q) use ($data) {
                if (! empty($data['species'])) {
                    $q->where('species', $data['species']);
                }
                if (! empty($data['size'])) {
                    $q->where('size', $data['size']);
                }
            });
        }

        $query->orderByRaw(
            'ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) ASC',
            [$lng, $lat]
        );
        $query->orderBy('id');

        $reports = $query->with(['pet.photos'])->paginateFromRequest();

        return PetReportResource::collection($reports);
    }

    public function lostMap(PetReportLostMapRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewLost', PetReport::class);

        $data = $request->validated();
        $lat = $data['latitude'];
        $lng = $data['longitude'];
        $radiusMeters = ($data['radius_km'] ?? 10) * 1000;

        $query = PetReport::query()
            ->where('status', PetReportStatus::Lost)
            ->where('is_active', true)
            ->whereHas('pet', fn ($q) => $q->whereNull('deleted_at'))
            ->whereNotNull('location')
            ->withCoordinates();

        $query->whereRaw(
            'ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $radiusMeters]
        );

        $query->orderByRaw(
            'ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) ASC',
            [$lng, $lat]
        );

        if (! empty($data['species']) || ! empty($data['size'])) {
            $query->whereHas('pet', function ($q) use ($data) {
                if (! empty($data['species'])) {
                    $q->where('species', $data['species']);
                }
                if (! empty($data['size'])) {
                    $q->where('size', $data['size']);
                }
            });
        }

        $reports = $query->with(['pet.photos'])->limit(500)->get();

        return PetReportResource::collection($reports);
    }

    public function found(PetReportFoundRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewFound', PetReport::class);

        $data = $request->validated();

        $query = PetReport::query()
            ->where('status', PetReportStatus::Found)
            ->where('is_active', true)
            ->whereHas('pet', fn ($q) => $q->whereNull('deleted_at'))
            ->withCoordinates();

        if (! empty($data['species']) || ! empty($data['size'])) {
            $query->whereHas('pet', function ($q) use ($data) {
                if (! empty($data['species'])) {
                    $q->where('species', $data['species']);
                }
                if (! empty($data['size'])) {
                    $q->where('size', $data['size']);
                }
            });
        }

        $query->orderByDesc('found_at');

        $reports = $query->with(['pet.photos'])->paginateFromRequest();

        return PetReportResource::collection($reports);
    }

    public function detail(PetReport $petReport): PetReportResource
    {
        Gate::authorize('viewDetail', $petReport);

        $petReport = PetReport::query()
            ->withCoordinates()
            ->withCount(['reportSightings', 'matches'])
            ->with(['pet.photos', 'pet.breed', 'pet.secondaryBreed', 'pet.characteristics'])
            ->find($petReport->id);

        return new PetReportResource($petReport);
    }

    public function store(StorePetReportRequest $request, StorePetReport $action): JsonResponse
    {
        Gate::authorize('create', PetReport::class);

        $data = new StorePetReportData(
            petId: $request->validated('pet_id'),
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
            addressHint: $request->validated('address_hint'),
            description: $request->validated('description'),
            lostAt: Carbon::parse($request->validated('lost_at')),
        );

        $report = $action->handle($data, $request->user());

        $report = PetReport::query()
            ->withCoordinates()
            ->with(['pet.photos'])
            ->find($report->id);

        return (new PetReportResource($report))
            ->response()
            ->setStatusCode(201);
    }

    public function show(PetReport $petReport): PetReportResource
    {
        Gate::authorize('view', $petReport);

        $petReport = PetReport::query()
            ->withCoordinates()
            ->withCount('matches')
            ->with(['pet.photos'])
            ->find($petReport->id);

        if (request()->user()->role === UserRole::Admin) {
            $petReport->load('user');
        }

        return new PetReportResource($petReport);
    }

    public function update(UpdatePetReportRequest $request, PetReport $petReport, UpdatePetReport $action): PetReportResource
    {
        Gate::authorize('update', $petReport);

        $data = new UpdatePetReportData(
            report: $petReport,
            latitude: $request->has('latitude') ? (float) $request->validated('latitude') : null,
            longitude: $request->has('longitude') ? (float) $request->validated('longitude') : null,
            addressHint: $request->has('address_hint') ? $request->validated('address_hint') : null,
            description: $request->has('description') ? $request->validated('description') : null,
            lostAt: $request->has('lost_at') ? Carbon::parse($request->validated('lost_at')) : null,
        );

        $action->handle($data);

        $petReport = PetReport::query()
            ->withCoordinates()
            ->with(['pet.photos', 'matches'])
            ->find($petReport->id);

        return new PetReportResource($petReport);
    }

    public function cancel(PetReport $petReport, CancelPetReport $action): PetReportResource
    {
        Gate::authorize('cancel', $petReport);

        $action->handle($petReport);

        $petReport = PetReport::query()
            ->withCoordinates()
            ->with(['pet.photos'])
            ->find($petReport->id);

        return new PetReportResource($petReport);
    }

    public function markFound(Request $request, PetReport $petReport, MarkPetReportFound $action): PetReportResource
    {
        Gate::authorize('markFound', $petReport);

        $confirmedMatchId = null;
        if ($request->has('confirmed_match_id')) {
            $request->validate([
                'confirmed_match_id' => ['required', 'integer'],
            ]);
            $confirmedMatchId = (int) $request->input('confirmed_match_id');
        }

        $action->handle($petReport, $confirmedMatchId);

        $petReport = PetReport::query()
            ->withCoordinates()
            ->with(['pet.photos'])
            ->find($petReport->id);

        return new PetReportResource($petReport);
    }

    public function matches(PetReport $petReport, Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewMatches', $petReport);

        $status = $request->query('status', 'PENDING');
        $request->validate([
            'status' => ['nullable', 'string', 'in:PENDING,CONFIRMED,DISMISSED'],
        ]);

        $matches = $petReport->matches()
            ->where('status', $status)
            ->with([
                'sighting.photos', 'sighting.characteristics', 'sighting.breed', 'sighting.user:id,name,avatar_url',
            ])
            ->orderByDesc('score')
            ->orderBy('distance_meters')
            ->orderBy('id')
            ->get();

        return PetMatchResource::collection($matches);
    }

    public function dismissMatch(PetReport $petReport, PetMatch $petMatch, DismissMatch $action): PetMatchResource
    {
        Gate::authorize('dismissMatch', $petReport);

        if ($petMatch->report_id !== $petReport->id) {
            abort(404, 'Match not found for this report.');
        }

        $match = $action->handle($petMatch);

        return new PetMatchResource($match);
    }

    public function confirmMatch(PetReport $petReport, PetMatch $petMatch, ConfirmMatch $action, MarkPetReportFound $markFound): PetMatchResource
    {
        Gate::authorize('confirmMatch', $petReport);

        if ($petMatch->report_id !== $petReport->id) {
            abort(404, 'Match not found for this report.');
        }

        $match = $action->handle($petMatch, $markFound);

        return new PetMatchResource($match);
    }
}
