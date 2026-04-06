<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\LogAdminAction;
use App\Http\Controllers\Controller;
use App\Models\PetSighting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SightingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search']);

        return Inertia::render('Sightings/Index', [
            'sightings' => PetSighting::query()
                ->with(['user:id,name'])
                ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('title', 'ilike', "%{$s}%"))
                ->paginateFromRequest('created_at', 'desc'),
            'filters' => $filters,
        ]);
    }

    public function show(PetSighting $sighting): Response
    {
        $sighting->load([
            'user:id,name,email',
            'breed:id,name',
            'photos',
            'characteristics',
            'matches' => fn ($q) => $q->with('report:id,status')->latest(),
        ]);

        return Inertia::render('Sightings/Show', [
            'sighting' => $sighting,
        ]);
    }

    public function destroy(Request $request, PetSighting $sighting): RedirectResponse
    {
        if ($sighting->trashed()) {
            return back()->with('warning', 'Este avistamento já foi removido.');
        }

        // TODO: Replace with DeleteSighting Action before production
        $sighting->delete();

        LogAdminAction::handle($request, 'sighting.delete', $sighting, $request->input('reason'));

        return redirect()->route('admin.sightings.index')->with('success', 'Avistamento removido.');
    }
}
