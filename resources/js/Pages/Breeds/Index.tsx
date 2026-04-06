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

const speciesLabels: Record<string, string> = {
    DOG: 'Cachorro',
    CAT: 'Gato',
};

export default function BreedsIndex() {
    const { breeds, filters } = usePage<BreedsPageProps>().props;

    const [search, setSearch] = useState(filters.search ?? '');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingBreed, setEditingBreed] = useState<Breed | null>(null);
    const [toggleBreed, setToggleBreed] = useState<Breed | null>(null);

    const form = useForm({
        name: '',
        species: 'DOG' as string,
    });

    function applyFilters(overrides: Partial<Filters> = {}) {
        const params: Record<string, string> = {};
        const merged = { ...filters, ...overrides };
        if (merged.search) params.search = merged.search;
        if (merged.species) params.species = merged.species;
        if (merged.active !== undefined) params.active = merged.active;
        if (merged.sort) params.sort = merged.sort;
        if (merged.direction) params.direction = merged.direction;

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
            form.put(`/admin/breeds/${editingBreed.id}`, {
                onSuccess: () => setDialogOpen(false),
            });
        } else {
            form.post('/admin/breeds', {
                onSuccess: () => setDialogOpen(false),
            });
        }
    }

    function handleToggle() {
        if (!toggleBreed) return;
        router.patch(`/admin/breeds/${toggleBreed.id}/toggle-active`, {}, {
            onSuccess: () => setToggleBreed(null),
        });
    }

    let debounceTimer: ReturnType<typeof setTimeout>;
    function handleSearch(value: string) {
        setSearch(value);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => applyFilters({ search: value }), 400);
    }

    return (
        <AdminLayout breadcrumbs={[{ label: 'Raças' }]}>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Raças</h1>
                <Button onClick={openCreate}>
                    <Plus className="mr-2 size-4" />
                    Nova Raça
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
                    value={filters.species ?? 'all'}
                    onValueChange={(v) => applyFilters({ species: v === 'all' ? undefined : v })}
                >
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="Espécie" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todas</SelectItem>
                        {speciesOptions.map((o) => (
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
                            <TableHead>Espécie</TableHead>
                            <TableHead className="text-center"># Pets</TableHead>
                            <TableHead className="text-center">Status</TableHead>
                            <TableHead className="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {breeds.data.length > 0 ? (
                            breeds.data.map((breed) => (
                                <TableRow key={breed.id}>
                                    <TableCell className="font-medium">{breed.name}</TableCell>
                                    <TableCell>{speciesLabels[breed.species] ?? breed.species}</TableCell>
                                    <TableCell className="text-center">{breed.pets_as_primary_count}</TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant={breed.is_active ? 'default' : 'secondary'}>
                                            {breed.is_active ? 'Ativo' : 'Inativo'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <Button variant="ghost" size="sm" onClick={() => openEdit(breed)}>
                                                <Pencil className="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => setToggleBreed(breed)}>
                                                <Power className={`size-4 ${breed.is_active ? 'text-destructive' : 'text-empet-success'}`} />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                    Nenhuma raça encontrada.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Pagination */}
            {breeds.last_page > 1 && (
                <div className="mt-4">
                    <Pagination>
                        <PaginationContent>
                            {breeds.prev_page_url && (
                                <PaginationItem>
                                    <PaginationPrevious href={breeds.prev_page_url} />
                                </PaginationItem>
                            )}
                            {Array.from({ length: breeds.last_page }, (_, i) => i + 1).map((page) => (
                                <PaginationItem key={page}>
                                    <PaginationLink
                                        href={`/admin/breeds?page=${page}`}
                                        isActive={page === breeds.current_page}
                                    >
                                        {page}
                                    </PaginationLink>
                                </PaginationItem>
                            ))}
                            {breeds.next_page_url && (
                                <PaginationItem>
                                    <PaginationNext href={breeds.next_page_url} />
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
                        <DialogTitle>{editingBreed ? 'Editar Raça' : 'Nova Raça'}</DialogTitle>
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
                            <Label htmlFor="species">Espécie</Label>
                            <Select
                                value={form.data.species}
                                onValueChange={(v) => form.setData('species', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {speciesOptions.map((o) => (
                                        <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.errors.species && (
                                <p className="text-sm text-destructive">{form.errors.species}</p>
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
            <AlertDialog open={!!toggleBreed} onOpenChange={(open) => !open && setToggleBreed(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {toggleBreed?.is_active ? 'Desativar raça?' : 'Ativar raça?'}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {toggleBreed?.is_active
                                ? `"${toggleBreed?.name}" não aparecerá em novos cadastros. Registros existentes não são afetados.`
                                : `"${toggleBreed?.name}" voltará a aparecer nos selects de cadastro.`}
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
