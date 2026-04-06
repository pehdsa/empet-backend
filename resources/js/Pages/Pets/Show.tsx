import { Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Power } from 'lucide-react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
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

interface Photo {
    id: number;
    url: string;
}

interface Report {
    id: number;
    status: string;
    created_at: string;
}

interface PetDetail {
    id: number;
    name: string;
    species: string;
    size: string | null;
    sex: string | null;
    primary_color: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
    breed: { id: number; name: string } | null;
    secondary_breed: { id: number; name: string } | null;
    photos: Photo[];
    characteristics: { id: number; name: string; category: string }[];
    reports: Report[];
}

interface PageProps extends SharedProps {
    pet: PetDetail;
}

const speciesLabels: Record<string, string> = { DOG: 'Cachorro', CAT: 'Gato' };
const statusLabels: Record<string, string> = { LOST: 'Perdido', FOUND: 'Encontrado', CANCELLED: 'Cancelado' };

export default function PetShow() {
    const { pet } = usePage<PageProps>().props;
    const [showConfirm, setShowConfirm] = useState(false);

    function handleToggle() {
        const action = pet.is_active ? 'deactivate' : 'reactivate';
        router.patch(`/admin/pets/${pet.id}/${action}`, {}, {
            onSuccess: () => setShowConfirm(false),
        });
    }

    return (
        <AdminLayout breadcrumbs={[{ label: 'Pets', href: '/admin/pets' }, { label: pet.name }]}>
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/admin/pets"><ArrowLeft className="size-4" /></Link>
                    </Button>
                    <h1 className="text-2xl font-bold">{pet.name}</h1>
                    <Badge variant={pet.is_active ? 'default' : 'secondary'}>
                        {pet.is_active ? 'Ativo' : 'Inativo'}
                    </Badge>
                </div>
                <Button
                    variant={pet.is_active ? 'destructive' : 'default'}
                    onClick={() => setShowConfirm(true)}
                >
                    <Power className="mr-2 size-4" />
                    {pet.is_active ? 'Desativar' : 'Reativar'}
                </Button>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Informações</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Espécie</span>
                            <span>{speciesLabels[pet.species] ?? pet.species}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Raça</span>
                            <span>{pet.breed?.name ?? '—'}{pet.secondary_breed ? ` / ${pet.secondary_breed.name}` : ''}</span>
                        </div>
                        {pet.size && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Porte</span>
                                <span>{pet.size}</span>
                            </div>
                        )}
                        {pet.sex && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Sexo</span>
                                <span>{pet.sex}</span>
                            </div>
                        )}
                        {pet.primary_color && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Cor</span>
                                <span>{pet.primary_color}</span>
                            </div>
                        )}
                        <Separator />
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Dono</span>
                            <span>{pet.user?.name ?? '—'} ({pet.user?.email})</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Cadastrado em</span>
                            <span>{new Date(pet.created_at).toLocaleDateString('pt-BR')}</span>
                        </div>
                        {pet.notes && (
                            <>
                                <Separator />
                                <p className="text-muted-foreground">{pet.notes}</p>
                            </>
                        )}
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    {pet.characteristics.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Características</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap gap-2">
                                {pet.characteristics.map((c) => (
                                    <Badge key={c.id} variant="outline">{c.name}</Badge>
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    {pet.photos.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Fotos</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap gap-2">
                                {pet.photos.map((photo) => (
                                    <img
                                        key={photo.id}
                                        src={photo.url}
                                        alt={pet.name}
                                        className="size-24 rounded-md object-cover"
                                    />
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Reports ({pet.reports.length})</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {pet.reports.length > 0 ? (
                                pet.reports.map((report) => (
                                    <div key={report.id} className="flex items-center justify-between text-sm">
                                        <Link href={`/admin/reports/${report.id}`} className="text-primary hover:underline">
                                            Report #{report.id}
                                        </Link>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="outline">
                                                {statusLabels[report.status] ?? report.status}
                                            </Badge>
                                            <span className="text-xs text-muted-foreground">
                                                {new Date(report.created_at).toLocaleDateString('pt-BR')}
                                            </span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">Nenhum report.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <AlertDialog open={showConfirm} onOpenChange={setShowConfirm}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {pet.is_active ? 'Desativar pet?' : 'Reativar pet?'}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {pet.is_active
                                ? 'Desativar este pet cancelará os reports em aberto e removerá matches pendentes. Confirma?'
                                : `Reativar "${pet.name}"? Reports cancelados e matches descartados não serão restaurados.`}
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
