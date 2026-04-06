import { Link, router, usePage } from '@inertiajs/react';
import { Eye, Trash2, Search } from 'lucide-react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import type { Paginated, Filters } from '@/Types/shared';
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
            {/* Title */}
            <h1 className="text-[22px] font-bold text-[#313233]">Avistamentos</h1>

            {/* Filters */}
            <div className="flex items-center gap-3">
                <div className="flex h-9 w-60 items-center gap-2 rounded-lg border border-[#E2E2E2] bg-white px-3">
                    <Search className="size-3.5 text-[#9B9C9D]" />
                    <input
                        placeholder="Buscar por título..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="flex-1 bg-transparent text-[13px] text-[#313233] outline-none placeholder:text-[#9B9C9D]"
                    />
                </div>
            </div>

            {/* Table */}
            <div className="overflow-hidden rounded-[10px] border border-[#E2E2E2] bg-white">
                {/* Header */}
                <div className="flex h-10 items-center bg-[#F8F8F8] px-4">
                    <span className="w-[200px] text-xs font-semibold text-[#6B6C6D]">Título</span>
                    <span className="w-[100px] text-xs font-semibold text-[#6B6C6D]">Espécie</span>
                    <span className="w-[140px] text-xs font-semibold text-[#6B6C6D]">Reportado por</span>
                    <span className="w-[100px] text-xs font-semibold text-[#6B6C6D]">Data</span>
                    <span className="flex-1 text-right text-xs font-semibold text-[#6B6C6D]">Ações</span>
                </div>

                {/* Rows */}
                {sightings.data.length > 0 ? sightings.data.map((sighting) => (
                    <div key={sighting.id} className="flex h-11 items-center border-t border-[#E2E2E2] px-4">
                        <span className="w-[200px] text-[13px] font-medium text-[#313233]">{sighting.title}</span>
                        <span className="w-[100px] text-[13px] text-[#6B6C6D]">{speciesLabels[sighting.species] ?? sighting.species}</span>
                        <span className="w-[140px] text-[13px] text-[#6B6C6D]">{sighting.user?.name ?? '—'}</span>
                        <span className="w-[100px] text-[13px] text-[#6B6C6D]">{new Date(sighting.created_at).toLocaleDateString('pt-BR')}</span>
                        <span className="flex flex-1 items-center justify-end gap-1">
                            <Link href={`/admin/sightings/${sighting.id}`} className="rounded p-1 hover:bg-[#F8F8F8]">
                                <Eye className="size-4 text-[#9B9C9D]" />
                            </Link>
                            <button onClick={() => setDeleting(sighting)} className="rounded p-1 hover:bg-[#F8F8F8]">
                                <Trash2 className="size-4 text-[#E53935]" />
                            </button>
                        </span>
                    </div>
                )) : (
                    <div className="flex h-20 items-center justify-center text-[13px] text-[#9B9C9D]">
                        Nenhum avistamento encontrado.
                    </div>
                )}
            </div>

            {/* Pagination */}
            {sightings.last_page > 1 && (
                <div className="flex items-center justify-center gap-1">
                    {sightings.prev_page_url && (
                        <Link href={sightings.prev_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Anterior</Link>
                    )}
                    {Array.from({ length: sightings.last_page }, (_, i) => i + 1).map((page) => (
                        <Link
                            key={page}
                            href={`/admin/sightings?page=${page}`}
                            className={`rounded px-2.5 py-1 text-[13px] ${page === sightings.current_page ? 'bg-primary text-white' : 'text-[#6B6C6D] hover:bg-[#E7E8E5]'}`}
                        >
                            {page}
                        </Link>
                    ))}
                    {sightings.next_page_url && (
                        <Link href={sightings.next_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Próximo</Link>
                    )}
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
