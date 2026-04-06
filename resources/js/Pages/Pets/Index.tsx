import { Link, router, usePage } from '@inertiajs/react';
import { Eye, Power } from 'lucide-react';
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
            <h1 className="text-2xl font-bold">Pets</h1>

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
                        <SelectItem value="DOG">Cachorro</SelectItem>
                        <SelectItem value="CAT">Gato</SelectItem>
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

            <div className="mt-4 rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nome</TableHead>
                            <TableHead>Espécie</TableHead>
                            <TableHead>Raça</TableHead>
                            <TableHead>Dono</TableHead>
                            <TableHead className="text-center">Status</TableHead>
                            <TableHead className="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {pets.data.length > 0 ? (
                            pets.data.map((pet) => (
                                <TableRow key={pet.id}>
                                    <TableCell className="font-medium">{pet.name}</TableCell>
                                    <TableCell>{speciesLabels[pet.species] ?? pet.species}</TableCell>
                                    <TableCell>{pet.breed?.name ?? '—'}</TableCell>
                                    <TableCell>{pet.user?.name ?? '—'}</TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant={pet.is_active ? 'default' : 'secondary'}>
                                            {pet.is_active ? 'Ativo' : 'Inativo'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/admin/pets/${pet.id}`}>
                                                    <Eye className="size-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => setToggling(pet)}>
                                                <Power className={`size-4 ${pet.is_active ? 'text-destructive' : 'text-empet-success'}`} />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                    Nenhum pet encontrado.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {pets.last_page > 1 && (
                <div className="mt-4">
                    <Pagination>
                        <PaginationContent>
                            {pets.prev_page_url && (
                                <PaginationItem>
                                    <PaginationPrevious href={pets.prev_page_url} />
                                </PaginationItem>
                            )}
                            {Array.from({ length: pets.last_page }, (_, i) => i + 1).map((page) => (
                                <PaginationItem key={page}>
                                    <PaginationLink href={`/admin/pets?page=${page}`} isActive={page === pets.current_page}>
                                        {page}
                                    </PaginationLink>
                                </PaginationItem>
                            ))}
                            {pets.next_page_url && (
                                <PaginationItem>
                                    <PaginationNext href={pets.next_page_url} />
                                </PaginationItem>
                            )}
                        </PaginationContent>
                    </Pagination>
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
