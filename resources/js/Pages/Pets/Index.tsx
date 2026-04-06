import { Link, router, usePage } from '@inertiajs/react';
import { Eye, Power, Search } from 'lucide-react';
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

interface Pet {
    id: number;
    name: string;
    species: string;
    is_active: boolean;
    created_at: string;
    user: { id: number; name: string } | null;
    breed: { id: number; name: string } | null;
}

interface PageProps extends SharedProps {
    pets: Paginated<Pet>;
    filters: Filters;
}

const speciesLabels: Record<string, string> = { DOG: 'Cachorro', CAT: 'Gato' };

export default function PetsIndex() {
    const { pets, filters } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search ?? '');
    const [toggling, setToggling] = useState<Pet | null>(null);

    function applyFilters(overrides: Partial<Filters> = {}) {
        const params: Record<string, string> = {};
        const merged = { ...filters, ...overrides };
        if (merged.search) params.search = merged.search;
        if (merged.species) params.species = merged.species;
        if (merged.active !== undefined) params.active = merged.active;
        router.get('/admin/pets', params, { preserveState: true });
    }

    function handleToggle() {
        if (!toggling) return;
        const action = toggling.is_active ? 'deactivate' : 'reactivate';
        router.patch(`/admin/pets/${toggling.id}/${action}`, {}, {
            onSuccess: () => setToggling(null),
        });
    }

    let debounceTimer: ReturnType<typeof setTimeout>;
    function handleSearch(value: string) {
        setSearch(value);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => applyFilters({ search: value }), 400);
    }

    return (
        <AdminLayout breadcrumbs={[{ label: 'Pets' }]}>
            {/* Title */}
            <h1 className="text-[22px] font-bold text-[#313233]">Pets</h1>

            {/* Filters */}
            <div className="flex items-center gap-3">
                <div className="flex h-9 w-60 items-center gap-2 rounded-lg border border-[#E2E2E2] bg-white px-3">
                    <Search className="size-3.5 text-[#9B9C9D]" />
                    <input
                        placeholder="Buscar por nome..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="flex-1 bg-transparent text-[13px] text-[#313233] outline-none placeholder:text-[#9B9C9D]"
                    />
                </div>
                <Select
                    value={filters.species ?? 'all'}
                    onValueChange={(v) => applyFilters({ species: v === 'all' ? undefined : v })}
                >
                    <SelectTrigger className="h-9 w-[150px] rounded-lg border-[#E2E2E2] bg-white text-[13px] text-[#6B6C6D]">
                        <SelectValue placeholder="Espécie" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todas</SelectItem>
                        <SelectItem value="DOG">Cachorro</SelectItem>
                        <SelectItem value="CAT">Gato</SelectItem>
                    </SelectContent>
                </Select>
                <Select
                    value={filters.active ?? 'all'}
                    onValueChange={(v) => applyFilters({ active: v === 'all' ? undefined : v })}
                >
                    <SelectTrigger className="h-9 w-[130px] rounded-lg border-[#E2E2E2] bg-white text-[13px] text-[#6B6C6D]">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        <SelectItem value="1">Ativo</SelectItem>
                        <SelectItem value="0">Inativo</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {/* Table */}
            <div className="overflow-hidden rounded-[10px] border border-[#E2E2E2] bg-white">
                {/* Header */}
                <div className="flex h-10 items-center bg-[#F8F8F8] px-4">
                    <span className="w-[160px] text-xs font-semibold text-[#6B6C6D]">Nome</span>
                    <span className="w-[100px] text-xs font-semibold text-[#6B6C6D]">Espécie</span>
                    <span className="w-[140px] text-xs font-semibold text-[#6B6C6D]">Raça</span>
                    <span className="w-[140px] text-xs font-semibold text-[#6B6C6D]">Dono</span>
                    <span className="w-20 text-center text-xs font-semibold text-[#6B6C6D]">Status</span>
                    <span className="flex-1 text-right text-xs font-semibold text-[#6B6C6D]">Ações</span>
                </div>

                {/* Rows */}
                {pets.data.length > 0 ? pets.data.map((pet) => (
                    <div key={pet.id} className="flex h-11 items-center border-t border-[#E2E2E2] px-4">
                        <span className="w-[160px] text-[13px] font-medium text-[#313233]">{pet.name}</span>
                        <span className="w-[100px] text-[13px] text-[#6B6C6D]">{speciesLabels[pet.species] ?? pet.species}</span>
                        <span className="w-[140px] text-[13px] text-[#6B6C6D]">{pet.breed?.name ?? '—'}</span>
                        <span className="w-[140px] text-[13px] text-[#6B6C6D]">{pet.user?.name ?? '—'}</span>
                        <span className="w-20 text-center">
                            <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                                pet.is_active ? 'bg-[#43A047] text-white' : 'bg-[#E7E8E5] text-[#6B6C6D]'
                            }`}>
                                {pet.is_active ? 'Ativo' : 'Inativo'}
                            </span>
                        </span>
                        <span className="flex flex-1 items-center justify-end gap-1">
                            <Link href={`/admin/pets/${pet.id}`} className="rounded p-1 hover:bg-[#F8F8F8]">
                                <Eye className="size-4 text-[#9B9C9D]" />
                            </Link>
                            <button onClick={() => setToggling(pet)} className="rounded p-1 hover:bg-[#F8F8F8]">
                                <Power className={`size-4 ${pet.is_active ? 'text-[#E53935]' : 'text-[#43A047]'}`} />
                            </button>
                        </span>
                    </div>
                )) : (
                    <div className="flex h-20 items-center justify-center text-[13px] text-[#9B9C9D]">
                        Nenhum pet encontrado.
                    </div>
                )}
            </div>

            {/* Pagination */}
            {pets.last_page > 1 && (
                <div className="flex items-center justify-center gap-1">
                    {pets.prev_page_url && (
                        <Link href={pets.prev_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Anterior</Link>
                    )}
                    {Array.from({ length: pets.last_page }, (_, i) => i + 1).map((page) => (
                        <Link
                            key={page}
                            href={`/admin/pets?page=${page}`}
                            className={`rounded px-2.5 py-1 text-[13px] ${page === pets.current_page ? 'bg-primary text-white' : 'text-[#6B6C6D] hover:bg-[#E7E8E5]'}`}
                        >
                            {page}
                        </Link>
                    ))}
                    {pets.next_page_url && (
                        <Link href={pets.next_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Próximo</Link>
                    )}
                </div>
            )}

            <AlertDialog open={!!toggling} onOpenChange={(open) => !open && setToggling(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {toggling?.is_active ? 'Desativar pet?' : 'Reativar pet?'}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {toggling?.is_active
                                ? 'Desativar este pet cancelará os reports em aberto e removerá matches pendentes. Confirma?'
                                : `Reativar "${toggling?.name}"? Reports cancelados e matches descartados não serão restaurados.`}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleToggle}>
                            Confirmar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    );
}
