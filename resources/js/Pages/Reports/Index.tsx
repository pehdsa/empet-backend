import { Link, router, usePage } from '@inertiajs/react';
import { Eye, Search } from 'lucide-react';
import { useState } from 'react';

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

interface Report {
    id: number;
    status: string;
    is_active: boolean;
    created_at: string;
    pet: { id: number; name: string; species: string } | null;
    user: { id: number; name: string } | null;
}

interface PageProps extends SharedProps {
    reports: Paginated<Report>;
    filters: Filters;
}

const statusLabels: Record<string, string> = {
    LOST: 'Perdido',
    FOUND: 'Encontrado',
    CANCELLED: 'Cancelado',
};

function statusBadgeClass(status: string): string {
    switch (status) {
        case 'LOST': return 'bg-[#E53935] text-white';
        case 'FOUND': return 'bg-[#43A047] text-white';
        case 'CANCELLED': return 'bg-[#E7E8E5] text-[#6B6C6D]';
        default: return 'bg-[#E7E8E5] text-[#6B6C6D]';
    }
}

export default function ReportsIndex() {
    const { reports, filters } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search ?? '');

    function applyFilters(overrides: Partial<Filters> = {}) {
        const params: Record<string, string> = {};
        const merged = { ...filters, ...overrides };
        if (merged.search) params.search = merged.search;
        if (merged.status) params.status = merged.status;
        if (merged.active !== undefined) params.active = merged.active;
        router.get('/admin/reports', params, { preserveState: true });
    }

    let debounceTimer: ReturnType<typeof setTimeout>;
    function handleSearch(value: string) {
        setSearch(value);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => applyFilters({ search: value }), 400);
    }

    return (
        <AdminLayout breadcrumbs={[{ label: 'Reports' }]}>
            {/* Title */}
            <h1 className="text-[22px] font-bold text-[#313233]">Reports</h1>

            {/* Filters */}
            <div className="flex items-center gap-3">
                <div className="flex h-9 w-60 items-center gap-2 rounded-lg border border-[#E2E2E2] bg-white px-3">
                    <Search className="size-3.5 text-[#9B9C9D]" />
                    <input
                        placeholder="Buscar por nome do pet..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="flex-1 bg-transparent text-[13px] text-[#313233] outline-none placeholder:text-[#9B9C9D]"
                    />
                </div>
                <Select
                    value={filters.status ?? 'all'}
                    onValueChange={(v) => applyFilters({ status: v === 'all' ? undefined : v })}
                >
                    <SelectTrigger className="h-9 w-[150px] rounded-lg border-[#E2E2E2] bg-white text-[13px] text-[#6B6C6D]">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        <SelectItem value="LOST">Perdido</SelectItem>
                        <SelectItem value="FOUND">Encontrado</SelectItem>
                        <SelectItem value="CANCELLED">Cancelado</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {/* Table */}
            <div className="overflow-hidden rounded-[10px] border border-[#E2E2E2] bg-white">
                {/* Header */}
                <div className="flex h-10 items-center bg-[#F8F8F8] px-4">
                    <span className="w-[160px] text-xs font-semibold text-[#6B6C6D]">Pet</span>
                    <span className="w-[140px] text-xs font-semibold text-[#6B6C6D]">Dono</span>
                    <span className="w-24 text-center text-xs font-semibold text-[#6B6C6D]">Status</span>
                    <span className="w-[100px] text-xs font-semibold text-[#6B6C6D]">Data</span>
                    <span className="flex-1 text-right text-xs font-semibold text-[#6B6C6D]">Ações</span>
                </div>

                {/* Rows */}
                {reports.data.length > 0 ? reports.data.map((report) => (
                    <div key={report.id} className="flex h-11 items-center border-t border-[#E2E2E2] px-4">
                        <span className="w-[160px] text-[13px] font-medium text-[#313233]">{report.pet?.name ?? '—'}</span>
                        <span className="w-[140px] text-[13px] text-[#6B6C6D]">{report.user?.name ?? '—'}</span>
                        <span className="w-24 text-center">
                            <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${statusBadgeClass(report.status)}`}>
                                {statusLabels[report.status] ?? report.status}
                            </span>
                        </span>
                        <span className="w-[100px] text-[13px] text-[#6B6C6D]">{new Date(report.created_at).toLocaleDateString('pt-BR')}</span>
                        <span className="flex flex-1 items-center justify-end gap-1">
                            <Link href={`/admin/reports/${report.id}`} className="rounded p-1 hover:bg-[#F8F8F8]">
                                <Eye className="size-4 text-[#9B9C9D]" />
                            </Link>
                        </span>
                    </div>
                )) : (
                    <div className="flex h-20 items-center justify-center text-[13px] text-[#9B9C9D]">
                        Nenhum report encontrado.
                    </div>
                )}
            </div>

            {/* Pagination */}
            {reports.last_page > 1 && (
                <div className="flex items-center justify-center gap-1">
                    {reports.prev_page_url && (
                        <Link href={reports.prev_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Anterior</Link>
                    )}
                    {Array.from({ length: reports.last_page }, (_, i) => i + 1).map((page) => (
                        <Link
                            key={page}
                            href={`/admin/reports?page=${page}`}
                            className={`rounded px-2.5 py-1 text-[13px] ${page === reports.current_page ? 'bg-primary text-white' : 'text-[#6B6C6D] hover:bg-[#E7E8E5]'}`}
                        >
                            {page}
                        </Link>
                    ))}
                    {reports.next_page_url && (
                        <Link href={reports.next_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Próximo</Link>
                    )}
                </div>
            )}
        </AdminLayout>
    );
}
