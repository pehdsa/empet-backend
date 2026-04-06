import { Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
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

interface Match {
    id: number;
    final_score: string | null;
    status: string;
    created_at: string;
    report: { id: number; status: string } | null;
}

interface SightingDetail {
    id: number;
    title: string;
    description: string | null;
    address_hint: string | null;
    species: string;
    size: string | null;
    sex: string | null;
    color: string | null;
    sighted_at: string | null;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
    breed: { id: number; name: string } | null;
    photos: Photo[];
    characteristics: { id: number; name: string; category: string }[];
    matches: Match[];
}

interface PageProps extends SharedProps {
    sighting: SightingDetail;
}

const speciesLabels: Record<string, string> = { DOG: 'Cachorro', CAT: 'Gato' };

const matchStatusLabels: Record<string, string> = {
    PENDING: 'Pendente',
    CONFIRMED: 'Confirmado',
    DISMISSED: 'Descartado',
};

export default function SightingShow() {
    const { sighting } = usePage<PageProps>().props;
    const [showDelete, setShowDelete] = useState(false);
    const [deleteReason, setDeleteReason] = useState('');

    function handleDelete() {
        router.delete(`/admin/sightings/${sighting.id}`, {
            data: { reason: deleteReason || undefined },
            onSuccess: () => setShowDelete(false),
        });
    }

    return (
        <AdminLayout breadcrumbs={[{ label: 'Avistamentos', href: '/admin/sightings' }, { label: sighting.title }]}>
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/admin/sightings"><ArrowLeft className="size-4" /></Link>
                    </Button>
                    <h1 className="text-2xl font-bold">{sighting.title}</h1>
                </div>
                <Button variant="destructive" onClick={() => setShowDelete(true)}>
                    <Trash2 className="mr-2 size-4" />
                    Remover
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
                            <span>{speciesLabels[sighting.species] ?? sighting.species}</span>
                        </div>
                        {sighting.breed && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Raça</span>
                                <span>{sighting.breed.name}</span>
                            </div>
                        )}
                        {sighting.size && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Porte</span>
                                <span>{sighting.size}</span>
                            </div>
                        )}
                        {sighting.sex && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Sexo</span>
                                <span>{sighting.sex}</span>
                            </div>
                        )}
                        {sighting.color && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Cor</span>
                                <span>{sighting.color}</span>
                            </div>
                        )}
                        {sighting.address_hint && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Local</span>
                                <span>{sighting.address_hint}</span>
                            </div>
                        )}
                        <Separator />
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Reportado por</span>
                            <span>{sighting.user?.name ?? '—'} {sighting.user?.email ? `(${sighting.user.email})` : ''}</span>
                        </div>
                        {sighting.sighted_at && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Avistado em</span>
                                <span>{new Date(sighting.sighted_at).toLocaleDateString('pt-BR')}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Criado em</span>
                            <span>{new Date(sighting.created_at).toLocaleDateString('pt-BR')}</span>
                        </div>
                        {sighting.description && (
                            <>
                                <Separator />
                                <p className="text-muted-foreground">{sighting.description}</p>
                            </>
                        )}
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    {sighting.characteristics.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Características</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap gap-2">
                                {sighting.characteristics.map((c) => (
                                    <Badge key={c.id} variant="outline">{c.name}</Badge>
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    {sighting.photos.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Fotos</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap gap-2">
                                {sighting.photos.map((photo) => (
                                    <img
                                        key={photo.id}
                                        src={photo.url}
                                        alt={sighting.title}
                                        className="size-24 rounded-md object-cover"
                                    />
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Matches ({sighting.matches.length})</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {sighting.matches.length > 0 ? (
                                sighting.matches.map((match) => (
                                    <div key={match.id} className="flex items-center justify-between text-sm">
                                        <Link href={`/admin/matches/${match.id}`} className="text-primary hover:underline">
                                            Match #{match.id}
                                        </Link>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="outline">
                                                {matchStatusLabels[match.status] ?? match.status}
                                            </Badge>
                                            {match.final_score && (
                                                <span className="text-xs text-muted-foreground">
                                                    Score: {match.final_score}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">Nenhum match.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <AlertDialog open={showDelete} onOpenChange={(open) => { if (!open) { setShowDelete(false); setDeleteReason(''); } }}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Remover avistamento?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Confirma a remoção do avistamento "{sighting.title}"?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="py-2">
                        <Label htmlFor="delete-reason">Motivo (opcional)</Label>
                        <Textarea
                            id="delete-reason"
                            placeholder="Motivo da remoção..."
                            value={deleteReason}
                            onChange={(e) => setDeleteReason(e.target.value)}
                            className="mt-1"
                        />
                    </div>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDelete}>
                            Confirmar Remoção
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    );
}
