import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Plus, Pencil, Power, Search } from 'lucide-react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import type { Paginated, Filters } from '@/Types/shared';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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

interface Breed {
    id: number;
    name: string;
    species: string;
    is_active: boolean;
    pets_as_primary_count: number;
    created_at: string;
}

interface BreedsPageProps extends SharedProps {
    breeds: Paginated<Breed>;
    filters: Filters;
}

const speciesOptions = [
    { value: 'DOG', label: 'Cachorro' },
    { value: 'CAT', label: 'Gato' },
];

const speciesLabels: Record<string, string> = { DOG: 'Cachorro', CAT: 'Gato' };

export default function BreedsIndex() {
    const { breeds, filters } = usePage<BreedsPageProps>().props;
    const [search, setSearch] = useState(filters.search ?? '');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingBreed, setEditingBreed] = useState<Breed | null>(null);
    const [toggleBreed, setToggleBreed] = useState<Breed | null>(null);

    const form = useForm({ name: '', species: 'DOG' as string });

    function applyFilters(overrides: Partial<Filters> = {}) {
        const params: Record<string, string> = {};
        const merged = { ...filters, ...overrides };
        if (merged.search) params.search = merged.search;
        if (merged.species) params.species = merged.species;
        if (merged.active !== undefined) params.active = merged.active;
        router.get('/admin/breeds', params, { preserveState: true });
    }

    function openCreate() {
        setEditingBreed(null);
        form.setData({ name: '', species: 'DOG' });
        form.clearErrors();
        setDialogOpen(true);
    }

    function openEdit(breed: Breed) {
        setEditingBreed(breed);
        form.setData({ name: breed.name, species: breed.species });
        form.clearErrors();
        setDialogOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editingBreed) {
            form.put(`/admin/breeds/${editingBreed.id}`, { onSuccess: () => setDialogOpen(false) });
        } else {
            form.post('/admin/breeds', { onSuccess: () => setDialogOpen(false) });
        }
    }

    function handleToggle() {
        if (!toggleBreed) return;
        router.patch(`/admin/breeds/${toggleBreed.id}/toggle-active`, {}, { onSuccess: () => setToggleBreed(null) });
    }

    let debounceTimer: ReturnType<typeof setTimeout>;
    function handleSearch(value: string) {
        setSearch(value);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => applyFilters({ search: value }), 400);
    }

    return (
        <AdminLayout breadcrumbs={[{ label: 'Raças' }]}>
            {/* Title + button */}
            <div className="flex items-center justify-between">
                <h1 className="text-[22px] font-bold text-[#313233]">Raças</h1>
                <button onClick={openCreate} className="flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-[13px] font-medium text-white hover:bg-[#CC8000]">
                    <Plus className="size-3.5" />
                    Nova Raça
                </button>
            </div>

            {/* Filters — gap-3 (12px) */}
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
                <Select value={filters.species ?? 'all'} onValueChange={(v) => applyFilters({ species: v === 'all' ? undefined : v })}>
                    <SelectTrigger className="h-9 w-[150px] rounded-lg border-[#E2E2E2] bg-white text-[13px] text-[#6B6C6D]">
                        <SelectValue placeholder="Espécie" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todas</SelectItem>
                        {speciesOptions.map((o) => <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>)}
                    </SelectContent>
                </Select>
                <Select value={filters.active ?? 'all'} onValueChange={(v) => applyFilters({ active: v === 'all' ? undefined : v })}>
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
                {/* Header — h-10 (40px), bg background */}
                <div className="flex h-10 items-center bg-[#F8F8F8] px-4">
                    <span className="w-[200px] text-xs font-semibold text-[#6B6C6D]">Nome</span>
                    <span className="w-[120px] text-xs font-semibold text-[#6B6C6D]">Espécie</span>
                    <span className="w-20 text-center text-xs font-semibold text-[#6B6C6D]"># Pets</span>
                    <span className="w-20 text-center text-xs font-semibold text-[#6B6C6D]">Status</span>
                    <span className="flex-1 text-right text-xs font-semibold text-[#6B6C6D]">Ações</span>
                </div>

                {/* Rows — h-11 (44px) each */}
                {breeds.data.length > 0 ? breeds.data.map((breed) => (
                    <div key={breed.id} className="flex h-11 items-center border-t border-[#E2E2E2] px-4">
                        <span className="w-[200px] text-[13px] font-medium text-[#313233]">{breed.name}</span>
                        <span className="w-[120px] text-[13px] text-[#6B6C6D]">{speciesLabels[breed.species] ?? breed.species}</span>
                        <span className="w-20 text-center text-[13px] text-[#6B6C6D]">{breed.pets_as_primary_count}</span>
                        <span className="w-20 text-center">
                            <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                                breed.is_active ? 'bg-[#43A047] text-white' : 'bg-[#E7E8E5] text-[#6B6C6D]'
                            }`}>
                                {breed.is_active ? 'Ativo' : 'Inativo'}
                            </span>
                        </span>
                        <span className="flex flex-1 items-center justify-end gap-1">
                            <button onClick={() => openEdit(breed)} className="rounded p-1 hover:bg-[#F8F8F8]">
                                <Pencil className="size-4 text-[#9B9C9D]" />
                            </button>
                            <button onClick={() => setToggleBreed(breed)} className="rounded p-1 hover:bg-[#F8F8F8]">
                                <Power className={`size-4 ${breed.is_active ? 'text-[#E53935]' : 'text-[#43A047]'}`} />
                            </button>
                        </span>
                    </div>
                )) : (
                    <div className="flex h-20 items-center justify-center text-[13px] text-[#9B9C9D]">
                        Nenhuma raça encontrada.
                    </div>
                )}
            </div>

            {/* Pagination */}
            {breeds.last_page > 1 && (
                <div className="flex items-center justify-center gap-1">
                    {breeds.prev_page_url && (
                        <Link href={breeds.prev_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Anterior</Link>
                    )}
                    {Array.from({ length: breeds.last_page }, (_, i) => i + 1).map((page) => (
                        <Link
                            key={page}
                            href={`/admin/breeds?page=${page}`}
                            className={`rounded px-2.5 py-1 text-[13px] ${page === breeds.current_page ? 'bg-primary text-white' : 'text-[#6B6C6D] hover:bg-[#E7E8E5]'}`}
                        >
                            {page}
                        </Link>
                    ))}
                    {breeds.next_page_url && (
                        <Link href={breeds.next_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Próximo</Link>
                    )}
                </div>
            )}

            {/* Create/Edit Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editingBreed ? 'Editar Raça' : 'Nova Raça'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="name" className="text-sm font-medium text-[#313233]">Nome</Label>
                            <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} autoFocus className="h-10 rounded-lg border-[#E2E2E2] text-sm" />
                            {form.errors.name && <p className="text-sm text-destructive">{form.errors.name}</p>}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-sm font-medium text-[#313233]">Espécie</Label>
                            <Select value={form.data.species} onValueChange={(v) => form.setData('species', v)}>
                                <SelectTrigger className="h-10 rounded-lg border-[#E2E2E2] text-sm"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {speciesOptions.map((o) => <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            {form.errors.species && <p className="text-sm text-destructive">{form.errors.species}</p>}
                        </div>
                        <div className="flex justify-end gap-2 pt-2">
                            <button type="button" onClick={() => setDialogOpen(false)} className="rounded-lg border border-[#E2E2E2] px-4 py-2 text-[13px] text-[#6B6C6D] hover:bg-[#F8F8F8]">Cancelar</button>
                            <button type="submit" disabled={form.processing} className="rounded-lg bg-primary px-4 py-2 text-[13px] font-medium text-white hover:bg-[#CC8000] disabled:opacity-50">
                                {form.processing ? 'Salvando...' : 'Salvar'}
                            </button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Toggle Confirmation */}
            <AlertDialog open={!!toggleBreed} onOpenChange={(open) => !open && setToggleBreed(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>{toggleBreed?.is_active ? 'Desativar raça?' : 'Ativar raça?'}</AlertDialogTitle>
                        <AlertDialogDescription>
                            {toggleBreed?.is_active
                                ? `"${toggleBreed?.name}" não aparecerá em novos cadastros. Registros existentes não são afetados.`
                                : `"${toggleBreed?.name}" voltará a aparecer nos selects de cadastro.`}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleToggle}>Confirmar</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    );
}
