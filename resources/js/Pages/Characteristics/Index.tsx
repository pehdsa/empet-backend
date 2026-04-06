import { router, useForm, usePage } from '@inertiajs/react';
import { Plus, Pencil, Power } from 'lucide-react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import type { Paginated, Filters } from '@/Types/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Características</h1>
                <Button onClick={openCreate}>
                    <Plus className="mr-2 size-4" />
                    Nova Característica
                </Button>
            </div>

            {/* Filters */}
            <div className="mt-4 flex flex-wrap items-center gap-3">
                <Input
                    placeholder="Buscar por nome..."
                    value={search}
                    onChange={(e) => handleSearch(e.target.value)}
                    className="w-64"
                />
                <Select
                    value={filters.category ?? 'all'}
                    onValueChange={(v) => applyFilters({ category: v === 'all' ? undefined : v })}
                >
                    <SelectTrigger className="w-44">
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
                    <SelectTrigger className="w-36">
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
            <div className="mt-4 rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nome</TableHead>
                            <TableHead>Categoria</TableHead>
                            <TableHead className="text-center">Status</TableHead>
                            <TableHead className="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {characteristics.data.length > 0 ? (
                            characteristics.data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">{item.name}</TableCell>
                                    <TableCell>{categoryLabels[item.category] ?? item.category}</TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant={item.is_active ? 'default' : 'secondary'}>
                                            {item.is_active ? 'Ativo' : 'Inativo'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <Button variant="ghost" size="sm" onClick={() => openEdit(item)}>
                                                <Pencil className="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => setToggling(item)}>
                                                <Power className={`size-4 ${item.is_active ? 'text-destructive' : 'text-empet-success'}`} />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={4} className="py-8 text-center text-muted-foreground">
                                    Nenhuma característica encontrada.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Pagination */}
            {characteristics.last_page > 1 && (
                <div className="mt-4">
                    <Pagination>
                        <PaginationContent>
                            {characteristics.prev_page_url && (
                                <PaginationItem>
                                    <PaginationPrevious href={characteristics.prev_page_url} />
                                </PaginationItem>
                            )}
                            {Array.from({ length: characteristics.last_page }, (_, i) => i + 1).map((page) => (
                                <PaginationItem key={page}>
                                    <PaginationLink
                                        href={`/admin/characteristics?page=${page}`}
                                        isActive={page === characteristics.current_page}
                                    >
                                        {page}
                                    </PaginationLink>
                                </PaginationItem>
                            ))}
                            {characteristics.next_page_url && (
                                <PaginationItem>
                                    <PaginationNext href={characteristics.next_page_url} />
                                </PaginationItem>
                            )}
                        </PaginationContent>
                    </Pagination>
                </div>
            )}

            {/* Create/Edit Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Editar Característica' : 'Nova Característica'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                autoFocus
                            />
                            {form.errors.name && (
                                <p className="text-sm text-destructive">{form.errors.name}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="category">Categoria</Label>
                            <Select
                                value={form.data.category}
                                onValueChange={(v) => form.setData('category', v)}
                            >
                                <SelectTrigger>
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
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Salvando...' : 'Salvar'}
                            </Button>
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
