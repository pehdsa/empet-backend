import { Link, router, usePage } from '@inertiajs/react';
import { Eye, Trash2 } from 'lucide-react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import type { Paginated, Filters } from '@/Types/shared';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Pagination, PaginationContent, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';

interface Sighting {
    id: number;
    title: string;
    species: string;
    created_at: string;
    user: { id: number; name: string } | null;
}

interface PageProps extends SharedProps {
    sightings: Paginated<Sighting>;
    filters: Filters;
}

const speciesLabels: Record<string, string> = { DOG: 'Cachorro', CAT: 'Gato' };

export default function SightingsIndex() {
    const { sightings, filters } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search ?? '');
    const [deleting, setDeleting] = useState<Sighting | null>(null);

    function applyFilters(overrides: Partial<Filters> = {}) {
        const params: Record<string, string> = {};
        const merged = { ...filters, ...overrides };
        if (merged.search) params.search = merged.search;
        router.get('/admin/sightings', params, { preserveState: true });
    }

    function handleDelete() {
        if (!deleting) return;
        router.delete(`/admin/sightings/${deleting.id}`, {
            onSuccess: () => setDeleting(null),
        });
    }

    let debounceTimer: ReturnType<typeof setTimeout>;
    function handleSearch(value: string) {
        setSearch(value);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => applyFilters({ search: value }), 400);
    }

    return (
        <AdminLayout breadcrumbs={[{ label: 'Avistamentos' }]}>
            <h1 className="text-2xl font-bold">Avistamentos</h1>

            <div className="mt-4 flex flex-wrap items-center gap-3">
                <Input
                    placeholder="Buscar por título..."
                    value={search}
                    onChange={(e) => handleSearch(e.target.value)}
                    className="w-64"
                />
            </div>

            <div className="mt-4 rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Título</TableHead>
                            <TableHead>Espécie</TableHead>
                            <TableHead>Reportado por</TableHead>
                            <TableHead>Data</TableHead>
                            <TableHead className="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {sightings.data.length > 0 ? (
                            sightings.data.map((sighting) => (
                                <TableRow key={sighting.id}>
                                    <TableCell className="font-medium">{sighting.title}</TableCell>
                                    <TableCell>{speciesLabels[sighting.species] ?? sighting.species}</TableCell>
                                    <TableCell>{sighting.user?.name ?? '—'}</TableCell>
                                    <TableCell>{new Date(sighting.created_at).toLocaleDateString('pt-BR')}</TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/admin/sightings/${sighting.id}`}>
                                                    <Eye className="size-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => setDeleting(sighting)}>
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                    Nenhum avistamento encontrado.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {sightings.last_page > 1 && (
                <div className="mt-4">
                    <Pagination>
                        <PaginationContent>
                            {sightings.prev_page_url && (
                                <PaginationItem>
                                    <PaginationPrevious href={sightings.prev_page_url} />
                                </PaginationItem>
                            )}
                            {Array.from({ length: sightings.last_page }, (_, i) => i + 1).map((page) => (
                                <PaginationItem key={page}>
                                    <PaginationLink href={`/admin/sightings?page=${page}`} isActive={page === sightings.current_page}>
                                        {page}
                                    </PaginationLink>
                                </PaginationItem>
                            ))}
                            {sightings.next_page_url && (
                                <PaginationItem>
                                    <PaginationNext href={sightings.next_page_url} />
                                </PaginationItem>
                            )}
                        </PaginationContent>
                    </Pagination>
                </div>
            )}

            <AlertDialog open={!!deleting} onOpenChange={(open) => !open && setDeleting(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Remover avistamento?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Confirma a remoção do avistamento "{deleting?.title}"? Esta ação pode ser desfeita.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDelete}>
                            Confirmar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    );
}
