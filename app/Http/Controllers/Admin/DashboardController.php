<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\PetMatch;
use App\Models\PetReport;
use App\Models\PetSighting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard/Index', [
            'kpis' => [
                'users' => User::query()->count(),
                'pets' => Pet::query()->where('is_active', true)->count(),
                'reportsOpen' => PetReport::query()->where('status', PetReportStatus::Lost)->count(),
                'reportsFound' => PetReport::query()->where('status', PetReportStatus::Found)->count(),
                'sightings' => PetSighting::query()->count(),
                'matchesPending' => PetMatch::query()->where('status', PetMatchStatus::Pending)->count(),
            ],
            'latestReports' => PetReport::query()
                ->with(['pet:id,name,species', 'user:id,name'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (PetReport $report) => [
                    'id' => $report->id,
                    'status' => $report->status->value,
                    'petName' => $report->pet?->name,
                    'petSpecies' => $report->pet?->species->value,
                    'ownerName' => $report->user->name,
                    'createdAt' => $report->created_at->toISOString(),
                ]),
            'latestSightings' => PetSighting::query()
                ->with('user:id,name')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (PetSighting $sighting) => [
                    'id' => $sighting->id,
                    'title' => $sighting->title,
                    'species' => $sighting->species->value,
                    'reporterName' => $sighting->user->name,
                    'sightedAt' => $sighting->sighted_at->toISOString(),
                ]),
            'reportsTimeline' => Inertia::optional(fn () => $this->reportsTimeline()),
            'matchesByStatus' => Inertia::optional(fn () => $this->matchesByStatus()),
        ]);
    }

    /**
     * @return array<int, array{date: string, count: int}>
     */
    private function reportsTimeline(): array
    {
        $from = Carbon::now()->subDays(29)->startOfDay();

        return PetReport::query()
            ->where('created_at', '>=', $from)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * @return array<int, array{status: string, count: int}>
     */
    private function matchesByStatus(): array
    {
        return PetMatch::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status->value,
                'count' => (int) $row->count,
            ])
            ->all();
    }
}
