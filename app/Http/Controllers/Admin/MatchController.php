<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\LogAdminAction;
use App\Enums\PetMatchStatus;
use App\Http\Controllers\Controller;
use App\Models\PetMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MatchController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'ai_status']);

        return Inertia::render('Matches/Index', [
            'matches' => PetMatch::query()
                ->with(['report:id,pet_id,status', 'report.pet:id,name', 'sighting:id,title'])
                ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
                ->when($filters['ai_status'] ?? null, fn ($q, $aiStatus) => $q->where('ai_status', $aiStatus))
                ->paginateFromRequest('created_at', 'desc'),
            'filters' => $filters,
        ]);
    }

    public function show(PetMatch $match): Response
    {
        $match->load([
            'report' => fn ($q) => $q->with(['pet' => fn ($pq) => $pq->with('photos'), 'user:id,name,email']),
            'sighting' => fn ($q) => $q->with(['photos', 'user:id,name,email']),
        ]);

        return Inertia::render('Matches/Show', [
            'match' => $match,
        ]);
    }

    public function dismiss(Request $request, PetMatch $match): RedirectResponse
    {
        if ($match->status !== PetMatchStatus::Pending) {
            return back()->with('warning', 'Apenas matches pendentes podem ser descartados.');
        }

        $match->update(['status' => PetMatchStatus::Dismissed]);

        LogAdminAction::handle($request, 'match.dismiss', $match, $request->input('reason'));

        return back()->with('success', 'Match descartado.');
    }
}
