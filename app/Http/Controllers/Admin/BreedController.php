<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\LogAdminAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Breed\StoreBreedRequest;
use App\Http\Requests\Admin\Breed\UpdateBreedRequest;
use App\Models\Breed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BreedController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'species', 'active']);

        return Inertia::render('Breeds/Index', [
            'breeds' => Breed::query()
                ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
                ->when(isset($filters['species']), fn ($q) => $q->where('species', $filters['species']))
                ->when(isset($filters['active']), fn ($q) => $q->where('is_active', $filters['active'] === '1'))
                ->withCount('petsAsPrimary')
                ->paginateFromRequest('name', 'asc'),
            'filters' => $filters,
        ]);
    }

    public function store(StoreBreedRequest $request): RedirectResponse
    {
        $breed = Breed::create($request->validated());

        LogAdminAction::handle($request, 'breed.create', $breed);

        return back()->with('success', 'Raça criada com sucesso.');
    }

    public function update(UpdateBreedRequest $request, Breed $breed): RedirectResponse
    {
        $old = $breed->only(['name', 'species']);

        $breed->update($request->validated());

        LogAdminAction::handle($request, 'breed.update', $breed, metadata: [
            'old' => $old,
            'new' => $breed->only(['name', 'species']),
        ]);

        return back()->with('success', 'Raça atualizada com sucesso.');
    }

    public function toggleActive(Request $request, Breed $breed): RedirectResponse
    {
        $breed->update(['is_active' => ! $breed->is_active]);

        LogAdminAction::handle($request, 'breed.toggle_active', $breed, metadata: [
            'is_active' => $breed->is_active,
        ]);

        return back()->with('success', $breed->is_active ? 'Raça ativada.' : 'Raça desativada.');
    }
}
