import { Link, router, usePage } from '@inertiajs/react';
import { Eye } from 'lucide-react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import type { Paginated, Filters } from '@/Types/shared';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Match {
    id: number;
    final_score: string | null;
    ai_status: string | null;
    status: string;
    created_at: string;
    report: { id: number; status: string; pet: { id: number; name: string } | null } | null;
    sighting: { id: number; title: string } | null;
}

interface PageProps extends SharedProps {
    matches: Paginated<Match>;
    filters: Filters;
}

const matchStatusLabels: Record<string, string> = {
    PENDING: 'Pendente',
    CONFIRMED: 'Confirmado',
    DISMISSED: 'Descartado',
};

function matchStatusBadgeClass(status: string): string {
    switch (status) {
        case 'PENDING': return 'bg-[#FEF3C7] text-[#92400E]';
        case 'CONFIRMED': return 'bg-[#DCFCE7] text-[#166534]';
        case 'DISMISSED': return 'bg-[#E7E8E5] text-[#6B6C6D]';
        default: return 'bg-[#E7E8E5] text-[#6B6C6D]';
    }
}

const aiStatusLabels: Record<string, string> = {
    PENDING: 'Pendente',
    PROCESSING: 'Processando',
    COMPLETED: 'Completo',
    FAILED: 'Falhou',
    SKIPPED: 'Ignorado',
};

function aiStatusBadgeClass(status: string | null): string {
    switch (status) {
        case 'COMPLETED': return 'bg-[#DCFCE7] text-[#166534]';
        case 'FAILED': return 'bg-[#E53935] text-white';
        case 'PROCESSING': return 'bg-[#FEF3C7] text-[#92400E]';
        case 'PENDING': return 'bg-[#FEF3C7] text-[#92400E]';
        case 'SKIPPED': return 'bg-[#E7E8E5] text-[#6B6C6D]';
        default: return 'bg-[#E7E8E5] text-[#6B6C6D]';
    }
}

export default function MatchesIndex() {
    const { matches, filters } = usePage<PageProps>().props;

    function applyFilters(overrides: Partial<Filters> = {}) {
        const params: Record<string, string> = {};
        const merged = { ...filters, ...overrides };
        if (merged.status) params.status = merged.status;
        if (merged.ai_status) params.ai_status = merged.ai_status;
        router.get('/admin/matches', params, { preserveState: true });
    }

    return (
        <AdminLayout breadcrumbs={[{ label: 'Matches' }]}>
            {/* Title */}
            <h1 className="text-[22px] font-bold text-[#313233]">Matches</h1>

            {/* Filters */}
            <div className="flex items-center gap-3">
                <Select
                    value={filters.status ?? 'all'}
                    onValueChange={(v) => applyFilters({ status: v === 'all' ? undefined : v })}
                >
                    <SelectTrigger className="h-9 w-[150px] rounded-lg border-[#E2E2E2] bg-white text-[13px] text-[#6B6C6D]">
                        <SelectValue placeholder="Status Match" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        <SelectItem value="PENDING">Pendente</SelectItem>
                        <SelectItem value="CONFIRMED">Confirmado</SelectItem>
                        <SelectItem value="DISMISSED">Descartado</SelectItem>
                    </SelectContent>
                </Select>
                <Select
                    value={filters.ai_status ?? 'all'}
                    onValueChange={(v) => applyFilters({ ai_status: v === 'all' ? undefined : v })}
                >
                    <SelectTrigger className="h-9 w-[150px] rounded-lg border-[#E2E2E2] bg-white text-[13px] text-[#6B6C6D]">
                        <SelectValue placeholder="Status IA" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        <SelectItem value="PENDING">Pendente</SelectItem>
                        <SelectItem value="PROCESSING">Processando</SelectItem>
                        <SelectItem value="COMPLETED">Completo</SelectItem>
                        <SelectItem value="FAILED">Falhou</SelectItem>
                        <SelectItem value="SKIPPED">Ignorado</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {/* Table */}
            <div className="overflow-hidden rounded-[10px] border border-[#E2E2E2] bg-white">
                {/* Header */}
                <div className="flex h-10 items-center bg-[#F8F8F8] px-4">
                    <span className="w-[70px] text-xs font-semibold text-[#6B6C6D]">Report</span>
                    <span className="w-[120px] text-xs font-semibold text-[#6B6C6D]">Pet</span>
                    <span className="w-[160px] text-xs font-semibold text-[#6B6C6D]">Avistamento</span>
                    <span className="w-[70px] text-center text-xs font-semibold text-[#6B6C6D]">Score</span>
                    <span className="w-[100px] text-center text-xs font-semibold text-[#6B6C6D]">Status IA</span>
                    <span className="w-[100px] text-center text-xs font-semibold text-[#6B6C6D]">Status Match</span>
                    <span className="flex-1 text-right text-xs font-semibold text-[#6B6C6D]">Ações</span>
                </div>

                {/* Rows */}
                {matches.data.length > 0 ? matches.data.map((match) => (
                    <div key={match.id} className="flex h-11 items-center border-t border-[#E2E2E2] px-4">
                        <span className="w-[70px] text-[13px]">
                            {match.report ? (
                                <Link href={`/admin/reports/${match.report.id}`} className="font-medium text-primary hover:underline">
                                    #{match.report.id}
                                </Link>
                            ) : <span className="text-[#6B6C6D]">—</span>}
                        </span>
                        <span className="w-[120px] text-[13px] font-medium text-[#313233]">{match.report?.pet?.name ?? '—'}</span>
                        <span className="w-[160px] text-[13px]">
                            {match.sighting ? (
                                <Link href={`/admin/sightings/${match.sighting.id}`} className="font-medium text-primary hover:underline">
                                    {match.sighting.title}
                                </Link>
                            ) : <span className="text-[#6B6C6D]">—</span>}
                        </span>
                        <span className="w-[70px] text-center text-[13px] text-[#6B6C6D]">{match.final_score ?? '—'}</span>
                        <span className="w-[100px] text-center">
                            <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${aiStatusBadgeClass(match.ai_status)}`}>
                                {match.ai_status ? (aiStatusLabels[match.ai_status] ?? match.ai_status) : '—'}
                            </span>
                        </span>
                        <span className="w-[100px] text-center">
                            <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${matchStatusBadgeClass(match.status)}`}>
                                {matchStatusLabels[match.status] ?? match.status}
                            </span>
                        </span>
                        <span className="flex flex-1 items-center justify-end gap-1">
                            <Link href={`/admin/matches/${match.id}`} className="rounded p-1 hover:bg-[#F8F8F8]">
                                <Eye className="size-4 text-[#9B9C9D]" />
                            </Link>
                        </span>
                    </div>
                )) : (
                    <div className="flex h-20 items-center justify-center text-[13px] text-[#9B9C9D]">
                        Nenhum match encontrado.
                    </div>
                )}
            </div>

            {/* Pagination */}
            {matches.last_page > 1 && (
                <div className="flex items-center justify-center gap-1">
                    {matches.prev_page_url && (
                        <Link href={matches.prev_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Anterior</Link>
                    )}
                    {Array.from({ length: matches.last_page }, (_, i) => i + 1).map((page) => (
                        <Link
                            key={page}
                            href={`/admin/matches?page=${page}`}
                            className={`rounded px-2.5 py-1 text-[13px] ${page === matches.current_page ? 'bg-primary text-white' : 'text-[#6B6C6D] hover:bg-[#E7E8E5]'}`}
                        >
                            {page}
                        </Link>
                    ))}
                    {matches.next_page_url && (
                        <Link href={matches.next_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Próximo</Link>
                    )}
                </div>
            )}
        </AdminLayout>
    );
}
