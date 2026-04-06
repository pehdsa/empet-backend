<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\LogAdminAction;
use App\Http\Controllers\Controller;
use App\Models\Pet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PetController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'species', 'active']);

        return Inertia::render('Pets/Index', [
            'pets' => Pet::query()
                ->with(['user:id,name', 'breed:id,name'])
                ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
                ->when(isset($filters['species']), fn ($q) => $q->where('species', $filters['species']))
                ->when(isset($filters['active']), fn ($q) => $q->where('is_active', $filters['active'] === '1'))
                ->paginateFromRequest('created_at', 'desc'),
            'filters' => $filters,
        ]);
    }

    public function show(Pet $pet): Response
    {
        $pet->load([
            'user:id,name,email',
            'breed:id,name',
            'secondaryBreed:id,name',
            'photos',
            'characteristics',
            'reports' => fn ($q) => $q->latest()->limit(10),
            'reports.matches' => fn ($q) => $q->latest()->limit(5),
        ]);

        return Inertia::render('Pets/Show', [
            'pet' => $pet,
        ]);
    }

    public function deactivate(Request $request, Pet $pet): RedirectResponse
    {
        if (! $pet->is_active) {
            return back()->with('warning', 'Pet já está inativo.');
        }

        // TODO: Replace with DeactivatePet Action (cascade) before production
        $pet->update(['is_active' => false]);

        LogAdminAction::handle($request, 'pet.deactivate', $pet);

        return back()->with('success', 'Pet desativado.');
    }

    public function reactivate(Request $request, Pet $pet): RedirectResponse
    {
        if ($pet->is_active) {
            return back()->with('warning', 'Pet já está ativo.');
        }

        $pet->update(['is_active' => true]);

        LogAdminAction::handle($request, 'pet.reactivate', $pet);

        return back()->with('success', 'Pet reativado. Reports e matches anteriores não foram restaurados.');
    }
}
