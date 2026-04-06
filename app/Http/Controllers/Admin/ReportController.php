<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\LogAdminAction;
use App\Enums\PetReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Report\CancelReportRequest;
use App\Models\PetReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'active']);

        return Inertia::render('Reports/Index', [
            'reports' => PetReport::query()
                ->with(['pet:id,name,species', 'user:id,name'])
                ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereHas('pet', fn ($pq) => $pq->where('name', 'ilike', "%{$s}%")))
                ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
                ->when(isset($filters['active']), fn ($q) => $q->where('is_active', $filters['active'] === '1'))
                ->paginateFromRequest('created_at', 'desc'),
            'filters' => $filters,
        ]);
    }

    public function show(PetReport $report): Response
    {
        $report->load([
            'pet' => fn ($q) => $q->with('photos'),
            'user:id,name,email',
            'matches' => fn ($q) => $q->with('sighting:id,title')->latest(),
            'reportSightings',
        ]);

        return Inertia::render('Reports/Show', [
            'report' => $report,
        ]);
    }

    public function cancel(CancelReportRequest $request, PetReport $report): RedirectResponse
    {
        if ($report->status === PetReportStatus::Cancelled) {
            return back()->with('error', 'Este report já está cancelado.');
        }

        // TODO: Replace with CancelReport Action before production
        $report->update(['status' => PetReportStatus::Cancelled]);

        LogAdminAction::handle($request, 'report.cancel', $report, $request->validated('reason'));

        return back()->with('success', 'Report cancelado.');
    }

    public function markFound(Request $request, PetReport $report): RedirectResponse
    {
        if ($report->status !== PetReportStatus::Lost) {
            return back()->with('error', 'Apenas reports com status "Perdido" podem ser marcados como encontrado.');
        }

        $report->update(['status' => PetReportStatus::Found]);

        LogAdminAction::handle($request, 'report.mark_found', $report);

        return back()->with('success', 'Report marcado como encontrado.');
    }
}
