import { Link, router, usePage } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import type { Paginated, Filters } from '@/Types/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Pagination, PaginationContent, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';

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

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    LOST: 'destructive',
    FOUND: 'default',
    CANCELLED: 'secondary',
};

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
            <h1 className="text-2xl font-bold">Reports</h1>

            <div className="mt-4 flex flex-wrap items-center gap-3">
                <Input
                    placeholder="Buscar por nome do pet..."
                    value={search}
                    onChange={(e) => handleSearch(e.target.value)}
                    className="w-64"
                />
                <Select
                    value={filters.status ?? 'all'}
                    onValueChange={(v) => applyFilters({ status: v === 'all' ? undefined : v })}
                >
                    <SelectTrigger className="w-40">
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

            <div className="mt-4 rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Pet</TableHead>
                            <TableHead>Dono</TableHead>
                            <TableHead className="text-center">Status</TableHead>
                            <TableHead>Data</TableHead>
                            <TableHead className="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {reports.data.length > 0 ? (
                            reports.data.map((report) => (
                                <TableRow key={report.id}>
                                    <TableCell className="font-medium">{report.pet?.name ?? '—'}</TableCell>
                                    <TableCell>{report.user?.name ?? '—'}</TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant={statusVariant[report.status] ?? 'outline'}>
                                            {statusLabels[report.status] ?? report.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{new Date(report.created_at).toLocaleDateString('pt-BR')}</TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link href={`/admin/reports/${report.id}`}>
                                                <Eye className="size-4" />
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                    Nenhum report encontrado.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {reports.last_page > 1 && (
                <div className="mt-4">
                    <Pagination>
                        <PaginationContent>
                            {reports.prev_page_url && (
                                <PaginationItem>
                                    <PaginationPrevious href={reports.prev_page_url} />
                                </PaginationItem>
                            )}
                            {Array.from({ length: reports.last_page }, (_, i) => i + 1).map((page) => (
                                <PaginationItem key={page}>
                                    <PaginationLink href={`/admin/reports?page=${page}`} isActive={page === reports.current_page}>
                                        {page}
                                    </PaginationLink>
                                </PaginationItem>
                            ))}
                            {reports.next_page_url && (
                                <PaginationItem>
                                    <PaginationNext href={reports.next_page_url} />
                                </PaginationItem>
                            )}
                        </PaginationContent>
                    </Pagination>
                </div>
            )}
        </AdminLayout>
    );
}
