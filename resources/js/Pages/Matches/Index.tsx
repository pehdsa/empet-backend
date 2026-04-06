import { Link, router, usePage } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import type { Paginated, Filters } from '@/Types/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

const matchStatusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    PENDING: 'outline',
    CONFIRMED: 'default',
    DISMISSED: 'secondary',
};

const aiStatusLabels: Record<string, string> = {
    PENDING: 'Pendente',
    PROCESSING: 'Processando',
    COMPLETED: 'Completo',
    FAILED: 'Falhou',
    SKIPPED: 'Ignorado',
};

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
            <h1 className="text-2xl font-bold">Matches</h1>

            <div className="mt-4 flex flex-wrap items-center gap-3">
                <Select
                    value={filters.status ?? 'all'}
                    onValueChange={(v) => applyFilters({ status: v === 'all' ? undefined : v })}
                >
                    <SelectTrigger className="w-40">
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
                    <SelectTrigger className="w-40">
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

            <div className="mt-4 rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Report</TableHead>
                            <TableHead>Pet</TableHead>
                            <TableHead>Avistamento</TableHead>
                            <TableHead className="text-center">Score</TableHead>
                            <TableHead className="text-center">Status IA</TableHead>
                            <TableHead className="text-center">Status Match</TableHead>
                            <TableHead className="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {matches.data.length > 0 ? (
                            matches.data.map((match) => (
                                <TableRow key={match.id}>
                                    <TableCell>
                                        {match.report ? (
                                            <Link href={`/admin/reports/${match.report.id}`} className="text-primary hover:underline">
                                                #{match.report.id}
                                            </Link>
                                        ) : '—'}
                                    </TableCell>
                                    <TableCell className="font-medium">{match.report?.pet?.name ?? '—'}</TableCell>
                                    <TableCell>
                                        {match.sighting ? (
                                            <Link href={`/admin/sightings/${match.sighting.id}`} className="text-primary hover:underline">
                                                {match.sighting.title}
                                            </Link>
                                        ) : '—'}
                                    </TableCell>
                                    <TableCell className="text-center">{match.final_score ?? '—'}</TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant="outline">
                                            {match.ai_status ? (aiStatusLabels[match.ai_status] ?? match.ai_status) : '—'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant={matchStatusVariant[match.status] ?? 'outline'}>
                                            {matchStatusLabels[match.status] ?? match.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link href={`/admin/matches/${match.id}`}>
                                                <Eye className="size-4" />
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={7} className="py-8 text-center text-muted-foreground">
                                    Nenhum match encontrado.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {matches.last_page > 1 && (
                <div className="mt-4">
                    <Pagination>
                        <PaginationContent>
                            {matches.prev_page_url && (
                                <PaginationItem>
                                    <PaginationPrevious href={matches.prev_page_url} />
                                </PaginationItem>
                            )}
                            {Array.from({ length: matches.last_page }, (_, i) => i + 1).map((page) => (
                                <PaginationItem key={page}>
                                    <PaginationLink href={`/admin/matches?page=${page}`} isActive={page === matches.current_page}>
                                        {page}
                                    </PaginationLink>
                                </PaginationItem>
                            ))}
                            {matches.next_page_url && (
                                <PaginationItem>
                                    <PaginationNext href={matches.next_page_url} />
                                </PaginationItem>
                            )}
                        </PaginationContent>
                    </Pagination>
                </div>
            )}
        </AdminLayout>
    );
}
