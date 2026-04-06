import { Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Ban, CheckCircle } from 'lucide-react';
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

interface Pet {
    id: number;
    name: string;
    species: string;
    photos: Photo[];
}

interface Match {
    id: number;
    final_score: string | null;
    status: string;
    created_at: string;
    sighting: { id: number; title: string } | null;
}

interface ReportSighting {
    id: number;
    created_at: string;
}

interface ReportDetail {
    id: number;
    status: string;
    is_active: boolean;
    description: string | null;
    address_hint: string | null;
    lost_at: string | null;
    found_at: string | null;
    created_at: string;
    pet: Pet | null;
    user: { id: number; name: string; email: string } | null;
    matches: Match[];
    report_sightings: ReportSighting[];
}

interface PageProps extends SharedProps {
    report: ReportDetail;
}

const statusLabels: Record<string, string> = {
    LOST: 'Perdido',
    FOUND: 'Encontrado',
    CANCELLED: 'Cancelado',
};

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    LOST: 'destructive',
    FOUND: 'default',
    CANCELLED: 'secondary',
};

const matchStatusLabels: Record<string, string> = {
    PENDING: 'Pendente',
    CONFIRMED: 'Confirmado',
    DISMISSED: 'Descartado',
};

export default function ReportShow() {
    const { report } = usePage<PageProps>().props;
    const [showCancel, setShowCancel] = useState(false);
    const [showFound, setShowFound] = useState(false);
    const [cancelReason, setCancelReason] = useState('');

    function handleCancel() {
        router.patch(`/admin/reports/${report.id}/cancel`, { reason: cancelReason }, {
            onSuccess: () => {
                setShowCancel(false);
                setCancelReason('');
            },
        });
    }

    function handleMarkFound() {
        router.patch(`/admin/reports/${report.id}/found`, {}, {
            onSuccess: () => setShowFound(false),
        });
    }

    return (
        <AdminLayout breadcrumbs={[{ label: 'Reports', href: '/admin/reports' }, { label: `Report #${report.id}` }]}>
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/admin/reports"><ArrowLeft className="size-4" /></Link>
                    </Button>
                    <h1 className="text-2xl font-bold">Report #{report.id}</h1>
                    <Badge variant={statusVariant[report.status] ?? 'outline'}>
                        {statusLabels[report.status] ?? report.status}
                    </Badge>
                </div>
                {report.status === 'LOST' && (
                    <div className="flex gap-2">
                        <Button variant="default" onClick={() => setShowFound(true)}>
                            <CheckCircle className="mr-2 size-4" />
                            Marcar Encontrado
                        </Button>
                        <Button variant="destructive" onClick={() => setShowCancel(true)}>
                            <Ban className="mr-2 size-4" />
                            Cancelar Report
                        </Button>
                    </div>
                )}
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Informações do Report</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Pet</span>
                            <span>
                                {report.pet ? (
                                    <Link href={`/admin/pets/${report.pet.id}`} className="text-primary hover:underline">
                                        {report.pet.name}
                                    </Link>
                                ) : '—'}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Dono</span>
                            <span>{report.user?.name ?? '—'} {report.user?.email ? `(${report.user.email})` : ''}</span>
                        </div>
                        {report.description && (
                            <>
                                <Separator />
                                <p className="text-muted-foreground">{report.description}</p>
                            </>
                        )}
                        {report.address_hint && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Local</span>
                                <span>{report.address_hint}</span>
                            </div>
                        )}
                        <Separator />
                        {report.lost_at && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Perdido em</span>
                                <span>{new Date(report.lost_at).toLocaleDateString('pt-BR')}</span>
                            </div>
                        )}
                        {report.found_at && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Encontrado em</span>
                                <span>{new Date(report.found_at).toLocaleDateString('pt-BR')}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Criado em</span>
                            <span>{new Date(report.created_at).toLocaleDateString('pt-BR')}</span>
                        </div>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    {report.pet && report.pet.photos.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Fotos do Pet</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap gap-2">
                                {report.pet.photos.map((photo) => (
                                    <img
                                        key={photo.id}
                                        src={photo.url}
                                        alt={report.pet?.name ?? 'Pet'}
                                        className="size-24 rounded-md object-cover"
                                    />
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Avistamentos do Report ({report.report_sightings.length})</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {report.report_sightings.length > 0 ? (
                                report.report_sightings.map((rs) => (
                                    <div key={rs.id} className="flex items-center justify-between text-sm">
                                        <span>Avistamento #{rs.id}</span>
                                        <span className="text-xs text-muted-foreground">
                                            {new Date(rs.created_at).toLocaleDateString('pt-BR')}
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">Nenhum avistamento do report.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Matches ({report.matches.length})</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {report.matches.length > 0 ? (
                                report.matches.map((match) => (
                                    <div key={match.id} className="flex items-center justify-between text-sm">
                                        <Link href={`/admin/matches/${match.id}`} className="text-primary hover:underline">
                                            Match #{match.id}
                                            {match.sighting ? ` — ${match.sighting.title}` : ''}
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

            <AlertDialog open={showCancel} onOpenChange={(open) => { if (!open) { setShowCancel(false); setCancelReason(''); } }}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Cancelar report?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta ação cancelará o report. Informe o motivo abaixo.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="py-2">
                        <Label htmlFor="cancel-reason">Motivo</Label>
                        <Textarea
                            id="cancel-reason"
                            placeholder="Motivo do cancelamento (mínimo 10 caracteres)..."
                            value={cancelReason}
                            onChange={(e) => setCancelReason(e.target.value)}
                            className="mt-1"
                        />
                    </div>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Voltar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleCancel} disabled={cancelReason.length < 10}>
                            Confirmar Cancelamento
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog open={showFound} onOpenChange={setShowFound}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Marcar como encontrado?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Confirma que este report deve ser marcado como encontrado?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Voltar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleMarkFound}>
                            Confirmar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    );
}
