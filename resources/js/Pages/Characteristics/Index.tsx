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

interface Characteristic {
    id: number;
    name: string;
    category: string;
    is_active: boolean;
    created_at: string;
}

interface PageProps extends SharedProps {
    characteristics: Paginated<Characteristic>;
    filters: Filters;
}

const categoryOptions = [
    { value: 'MARKING', label: 'Marcação' },
    { value: 'COAT', label: 'Pelagem' },
    { value: 'BEHAVIOR', label: 'Comportamento' },
    { value: 'IDENTIFICATION', label: 'Identificação' },
];

const categoryLabels: Record<string, string> = Object.fromEntries(
    categoryOptions.map((o) => [o.value, o.label]),
);

export default function CharacteristicsIndex() {
    const { characteristics, filters } = usePage<PageProps>().props;

    const [search, setSearch] = useState(filters.search ?? '');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<Characteristic | null>(null);
    const [toggling, setToggling] = useState<Characteristic | null>(null);

    const form = useForm({
        name: '',
        category: 'MARKING' as string,
    });

    function applyFilters(overrides: Partial<Filters> = {}) {
        const params: Record<string, string> = {};
        const merged = { ...filters, ...overrides };
        if (merged.search) params.search = merged.search;
        if (merged.category) params.category = merged.category;
        if (merged.active !== undefined) params.active = merged.active;
        router.get('/admin/characteristics', params, { preserveState: true });
    }

    function openCreate() {
        setEditing(null);
        form.setData({ name: '', category: 'MARKING' });
        form.clearErrors();
        setDialogOpen(true);
    }

    function openEdit(item: Characteristic) {
        setEditing(item);
        form.setData({ name: item.name, category: item.category });
        form.clearErrors();
        setDialogOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            form.put(`/admin/characteristics/${editing.id}`, {
                onSuccess: () => setDialogOpen(false),
            });
        } else {
            form.post('/admin/characteristics', {
                onSuccess: () => setDialogOpen(false),
            });
        }
    }

    function handleToggle() {
        if (!toggling) return;
        router.patch(`/admin/characteristics/${toggling.id}/toggle-active`, {}, {
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
        <AdminLayout breadcrumbs={[{ label: 'Características' }]}>
            {/* Title + button */}
            <div className="flex items-center justify-between">
                <h1 className="text-[22px] font-bold text-[#313233]">Características</h1>
                <button onClick={openCreate} className="flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-[13px] font-medium text-white hover:bg-[#CC8000]">
                    <Plus className="size-3.5" />
                    Nova Característica
                </button>
            </div>

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
                    value={filters.category ?? 'all'}
                    onValueChange={(v) => applyFilters({ category: v === 'all' ? undefined : v })}
                >
                    <SelectTrigger className="h-9 w-[150px] rounded-lg border-[#E2E2E2] bg-white text-[13px] text-[#6B6C6D]">
                        <SelectValue placeholder="Categoria" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todas</SelectItem>
                        {categoryOptions.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
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
                    <span className="w-[200px] text-xs font-semibold text-[#6B6C6D]">Nome</span>
                    <span className="w-[150px] text-xs font-semibold text-[#6B6C6D]">Categoria</span>
                    <span className="w-20 text-center text-xs font-semibold text-[#6B6C6D]">Status</span>
                    <span className="flex-1 text-right text-xs font-semibold text-[#6B6C6D]">Ações</span>
                </div>

                {/* Rows */}
                {characteristics.data.length > 0 ? characteristics.data.map((item) => (
                    <div key={item.id} className="flex h-11 items-center border-t border-[#E2E2E2] px-4">
                        <span className="w-[200px] text-[13px] font-medium text-[#313233]">{item.name}</span>
                        <span className="w-[150px] text-[13px] text-[#6B6C6D]">{categoryLabels[item.category] ?? item.category}</span>
                        <span className="w-20 text-center">
                            <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                                item.is_active ? 'bg-[#43A047] text-white' : 'bg-[#E7E8E5] text-[#6B6C6D]'
                            }`}>
                                {item.is_active ? 'Ativo' : 'Inativo'}
                            </span>
                        </span>
                        <span className="flex flex-1 items-center justify-end gap-1">
                            <button onClick={() => openEdit(item)} className="rounded p-1 hover:bg-[#F8F8F8]">
                                <Pencil className="size-4 text-[#9B9C9D]" />
                            </button>
                            <button onClick={() => setToggling(item)} className="rounded p-1 hover:bg-[#F8F8F8]">
                                <Power className={`size-4 ${item.is_active ? 'text-[#E53935]' : 'text-[#43A047]'}`} />
                            </button>
                        </span>
                    </div>
                )) : (
                    <div className="flex h-20 items-center justify-center text-[13px] text-[#9B9C9D]">
                        Nenhuma característica encontrada.
                    </div>
                )}
            </div>

            {/* Pagination */}
            {characteristics.last_page > 1 && (
                <div className="flex items-center justify-center gap-1">
                    {characteristics.prev_page_url && (
                        <Link href={characteristics.prev_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Anterior</Link>
                    )}
                    {Array.from({ length: characteristics.last_page }, (_, i) => i + 1).map((page) => (
                        <Link
                            key={page}
                            href={`/admin/characteristics?page=${page}`}
                            className={`rounded px-2.5 py-1 text-[13px] ${page === characteristics.current_page ? 'bg-primary text-white' : 'text-[#6B6C6D] hover:bg-[#E7E8E5]'}`}
                        >
                            {page}
                        </Link>
                    ))}
                    {characteristics.next_page_url && (
                        <Link href={characteristics.next_page_url} className="rounded px-2 py-1 text-[13px] text-[#6B6C6D] hover:bg-[#E7E8E5]">Próximo</Link>
                    )}
                </div>
            )}

            {/* Create/Edit Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Editar Característica' : 'Nova Característica'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="name" className="text-sm font-medium text-[#313233]">Nome</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                autoFocus
                                className="h-10 rounded-lg border-[#E2E2E2] text-sm"
                            />
                            {form.errors.name && (
                                <p className="text-sm text-destructive">{form.errors.name}</p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-sm font-medium text-[#313233]">Categoria</Label>
                            <Select
                                value={form.data.category}
                                onValueChange={(v) => form.setData('category', v)}
                            >
                                <SelectTrigger className="h-10 rounded-lg border-[#E2E2E2] text-sm">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {categoryOptions.map((o) => (
                                        <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.errors.category && (
                                <p className="text-sm text-destructive">{form.errors.category}</p>
                            )}
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

            {/* Toggle Active Confirmation */}
            <AlertDialog open={!!toggling} onOpenChange={(open) => !open && setToggling(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {toggling?.is_active ? 'Desativar característica?' : 'Ativar característica?'}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {toggling?.is_active
                                ? `"${toggling?.name}" não aparecerá em novos cadastros. Registros existentes não são afetados.`
                                : `"${toggling?.name}" voltará a aparecer nos selects de cadastro.`}
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
