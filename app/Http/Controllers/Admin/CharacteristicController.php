<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\LogAdminAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Characteristic\StoreCharacteristicRequest;
use App\Http\Requests\Admin\Characteristic\UpdateCharacteristicRequest;
use App\Models\Characteristic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CharacteristicController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category', 'active']);

        return Inertia::render('Characteristics/Index', [
            'characteristics' => Characteristic::query()
                ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
                ->when(isset($filters['category']), fn ($q) => $q->where('category', $filters['category']))
                ->when(isset($filters['active']), fn ($q) => $q->where('is_active', $filters['active'] === '1'))
                ->paginateFromRequest('name', 'asc'),
            'filters' => $filters,
        ]);
    }

    public function store(StoreCharacteristicRequest $request): RedirectResponse
    {
        $characteristic = Characteristic::create($request->validated());

        LogAdminAction::handle($request, 'characteristic.create', $characteristic);

        return back()->with('success', 'Característica criada com sucesso.');
    }

    public function update(UpdateCharacteristicRequest $request, Characteristic $characteristic): RedirectResponse
    {
        $old = $characteristic->only(['name', 'category']);

        $characteristic->update($request->validated());

        LogAdminAction::handle($request, 'characteristic.update', $characteristic, metadata: [
            'old' => $old,
            'new' => $characteristic->only(['name', 'category']),
        ]);

        return back()->with('success', 'Característica atualizada com sucesso.');
    }

    public function toggleActive(Request $request, Characteristic $characteristic): RedirectResponse
    {
        $characteristic->update(['is_active' => ! $characteristic->is_active]);

        LogAdminAction::handle($request, 'characteristic.toggle_active', $characteristic, metadata: [
            'is_active' => $characteristic->is_active,
        ]);

        return back()->with('success', $characteristic->is_active ? 'Característica ativada.' : 'Característica desativada.');
    }
}
