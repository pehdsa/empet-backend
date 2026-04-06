import { Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, XCircle } from 'lucide-react';
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

interface Pet {
    id: number;
    name: string;
    species: string;
    photos: Photo[];
}

interface Report {
    id: number;
    status: string;
    description: string | null;
    address_hint: string | null;
    created_at: string;
    pet: Pet | null;
    user: { id: number; name: string; email: string } | null;
}

interface Sighting {
    id: number;
    title: string;
    description: string | null;
    address_hint: string | null;
    species: string;
    created_at: string;
    photos: Photo[];
    user: { id: number; name: string; email: string } | null;
}

interface MatchDetail {
    id: number;
    base_score: string | null;
    ai_score: string | null;
    ai_confidence: string | null;
    ai_status: string | null;
    ai_summary: string | null;
    final_score: string | null;
    distance_meters: string | null;
    status: string;
    created_at: string;
    report: Report | null;
    sighting: Sighting | null;
}

interface PageProps extends SharedProps {
    match: MatchDetail;
}

const matchStatusLabels: Record<string, string> = {
    PENDING: 'Pendente',
    CONFIRMED: 'Confirmado',
    DISMISSED: 'Descartado',
};

const matchStatusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    PENDING: 'outline',
    CONFIRMED: 'default',
    DISMISSED: 'secondary',
};

const reportStatusLabels: Record<string, string> = {
    LOST: 'Perdido',
    FOUND: 'Encontrado',
    CANCELLED: 'Cancelado',
};

const aiStatusLabels: Record<string, string> = {
    PENDING: 'Pendente',
    PROCESSING: 'Processando',
    COMPLETED: 'Completo',
    FAILED: 'Falhou',
    SKIPPED: 'Ignorado',
};

export default function MatchShow() {
    const { match } = usePage<PageProps>().props;
    const [showDismiss, setShowDismiss] = useState(false);

    function handleDismiss() {
        router.patch(`/admin/matches/${match.id}/dismiss`, {}, {
            onSuccess: () => setShowDismiss(false),
        });
    }

    return (
        <AdminLayout breadcrumbs={[{ label: 'Matches', href: '/admin/matches' }, { label: `Match #${match.id}` }]}>
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/admin/matches"><ArrowLeft className="size-4" /></Link>
                    </Button>
                    <h1 className="text-2xl font-bold">Match #{match.id}</h1>
                    <Badge variant={matchStatusVariant[match.status] ?? 'outline'}>
                        {matchStatusLabels[match.status] ?? match.status}
                    </Badge>
                </div>
                {match.status === 'PENDING' && (
                    <Button variant="destructive" onClick={() => setShowDismiss(true)}>
                        <XCircle className="mr-2 size-4" />
                        Descartar
                    </Button>
                )}
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                {/* Left: Report info */}
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Report</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {match.report ? (
                                <>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Report</span>
                                        <Link href={`/admin/reports/${match.report.id}`} className="text-primary hover:underline">
                                            #{match.report.id}
                                        </Link>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Status</span>
                                        <Badge variant="outline">
                                            {reportStatusLabels[match.report.status] ?? match.report.status}
                                        </Badge>
                                    </div>
                                    {match.report.pet && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Pet</span>
                                            <Link href={`/admin/pets/${match.report.pet.id}`} className="text-primary hover:underline">
                                                {match.report.pet.name}
                                            </Link>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Dono</span>
                                        <span>{match.report.user?.name ?? '—'}</span>
                                    </div>
                                    {match.report.description && (
                                        <>
                                            <Separator />
                                            <p className="text-muted-foreground">{match.report.description}</p>
                                        </>
                                    )}
                                </>
                            ) : (
                                <p className="text-muted-foreground">Report não disponível.</p>
                            )}
                        </CardContent>
                    </Card>

                    {match.report?.pet && match.report.pet.photos.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Fotos do Pet</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap gap-2">
                                {match.report.pet.photos.map((photo) => (
                                    <img
                                        key={photo.id}
                                        src={photo.url}
                                        alt={match.report?.pet?.name ?? 'Pet'}
                                        className="size-24 rounded-md object-cover"
                                    />
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Right: Sighting info */}
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Avistamento</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {match.sighting ? (
                                <>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Avistamento</span>
                                        <Link href={`/admin/sightings/${match.sighting.id}`} className="text-primary hover:underline">
                                            {match.sighting.title}
                                        </Link>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Reportado por</span>
                                        <span>{match.sighting.user?.name ?? '—'}</span>
                                    </div>
                                    {match.sighting.address_hint && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Local</span>
                                            <span>{match.sighting.address_hint}</span>
                                        </div>
                                    )}
                                    {match.sighting.description && (
                                        <>
                                            <Separator />
                                            <p className="text-muted-foreground">{match.sighting.description}</p>
                                        </>
                                    )}
                                </>
                            ) : (
                                <p className="text-muted-foreground">Avistamento não disponível.</p>
                            )}
                        </CardContent>
                    </Card>

                    {match.sighting && match.sighting.photos.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Fotos do Avistamento</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap gap-2">
                                {match.sighting.photos.map((photo) => (
                                    <img
                                        key={photo.id}
                                        src={photo.url}
                                        alt={match.sighting?.title ?? 'Avistamento'}
                                        className="size-24 rounded-md object-cover"
                                    />
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>

            {/* AI Info Card - full width */}
            <Card className="mt-6">
                <CardHeader>
                    <CardTitle className="text-base">Informações da IA</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <span className="text-sm text-muted-foreground">Status IA</span>
                            <div className="mt-1">
                                <Badge variant="outline">
                                    {match.ai_status ? (aiStatusLabels[match.ai_status] ?? match.ai_status) : '—'}
                                </Badge>
                            </div>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">Score IA</span>
                            <p className="mt-1 font-medium">{match.ai_score ?? '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">Confiança IA</span>
                            <p className="mt-1 font-medium">{match.ai_confidence ?? '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">Score Final</span>
                            <p className="mt-1 font-medium">{match.final_score ?? '—'}</p>
                        </div>
                    </div>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <span className="text-sm text-muted-foreground">Score Base</span>
                            <p className="mt-1 font-medium">{match.base_score ?? '—'}</p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">Distância</span>
                            <p className="mt-1 font-medium">
                                {match.distance_meters ? `${Number(match.distance_meters).toFixed(0)}m` : '—'}
                            </p>
                        </div>
                    </div>
                    {match.ai_summary && (
                        <div className="mt-4">
                            <span className="text-sm text-muted-foreground">Resumo IA</span>
                            <p className="mt-1 rounded-md bg-muted p-3 text-sm">{match.ai_summary}</p>
                        </div>
                    )}
                </CardContent>
            </Card>

            <AlertDialog open={showDismiss} onOpenChange={setShowDismiss}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Descartar match?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Confirma que este match deve ser descartado? Esta ação marcará o match como descartado.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDismiss}>
                            Confirmar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    );
}
