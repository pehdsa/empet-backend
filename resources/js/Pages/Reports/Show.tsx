import { Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Ban, CheckCircle } from 'lucide-react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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

function statusBadgeClass(status: string): string {
    switch (status) {
        case 'LOST': return 'bg-[#E53935] text-white';
        case 'FOUND': return 'bg-[#43A047] text-white';
        case 'CANCELLED': return 'bg-[#E7E8E5] text-[#6B6C6D]';
        default: return 'bg-[#E7E8E5] text-[#6B6C6D]';
    }
}

const matchStatusLabels: Record<string, string> = {
    PENDING: 'Pendente',
    CONFIRMED: 'Confirmado',
    DISMISSED: 'Descartado',
};

function matchStatusBadgeClass(status: string): string {
    switch (status) {
        case 'PENDING': return 'bg-[#FEF3C7] text-[#92400E]';
        case 'CONFIRMED': return 'bg-[#DCFCE7] text-[#166534]';
        case 'DISMISSED': return 'bg-[#E7E8E5] text-[#6B6C6D]';
        default: return 'bg-[#E7E8E5] text-[#6B6C6D]';
    }
}

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
            {/* Title row */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link href="/admin/reports" className="rounded p-1 hover:bg-[#F8F8F8]">
                        <ArrowLeft className="size-4 text-[#9B9C9D]" />
                    </Link>
                    <h1 className="text-[22px] font-bold text-[#313233]">Report #{report.id}</h1>
                    <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${statusBadgeClass(report.status)}`}>
                        {statusLabels[report.status] ?? report.status}
                    </span>
                </div>
                {report.status === 'LOST' && (
                    <div className="flex gap-2">
                        <button onClick={() => setShowFound(true)} className="flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-[13px] font-medium text-white hover:bg-[#CC8000]">
                            <CheckCircle className="size-3.5" />
                            Marcar Encontrado
                        </button>
                        <button onClick={() => setShowCancel(true)} className="flex items-center gap-1.5 rounded-lg border border-[#E53935] px-4 py-2 text-[13px] font-medium text-[#E53935] hover:bg-red-50">
                            <Ban className="size-3.5" />
                            Cancelar Report
                        </button>
                    </div>
                )}
            </div>

            {/* Content grid */}
            <div className="grid gap-6 lg:grid-cols-2">
                {/* Info card */}
                <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                    <h2 className="text-sm font-semibold text-[#313233]">Informações do Report</h2>
                    <div className="mt-4 flex flex-col gap-2.5">
                        <div className="flex justify-between">
                            <span className="text-[13px] text-[#9B9C9D]">Pet</span>
                            <span className="text-[13px] font-medium text-[#313233]">
                                {report.pet ? (
                                    <Link href={`/admin/pets/${report.pet.id}`} className="text-primary hover:underline">
                                        {report.pet.name}
                                    </Link>
                                ) : '—'}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-[13px] text-[#9B9C9D]">Dono</span>
                            <span className="text-[13px] font-medium text-[#313233]">{report.user?.name ?? '—'} {report.user?.email ? `(${report.user.email})` : ''}</span>
                        </div>
                        {report.description && (
                            <>
                                <div className="border-t border-[#E2E2E2]" />
                                <p className="text-[13px] text-[#6B6C6D]">{report.description}</p>
                            </>
                        )}
                        {report.address_hint && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Local</span>
                                <span className="text-[13px] font-medium text-[#313233]">{report.address_hint}</span>
                            </div>
                        )}
                        <div className="border-t border-[#E2E2E2]" />
                        {report.lost_at && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Perdido em</span>
                                <span className="text-[13px] font-medium text-[#313233]">{new Date(report.lost_at).toLocaleDateString('pt-BR')}</span>
                            </div>
                        )}
                        {report.found_at && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Encontrado em</span>
                                <span className="text-[13px] font-medium text-[#313233]">{new Date(report.found_at).toLocaleDateString('pt-BR')}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-[13px] text-[#9B9C9D]">Criado em</span>
                            <span className="text-[13px] font-medium text-[#313233]">{new Date(report.created_at).toLocaleDateString('pt-BR')}</span>
                        </div>
                    </div>
                </div>

                <div className="flex flex-col gap-6">
                    {/* Photos */}
                    {report.pet && report.pet.photos.length > 0 && (
                        <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                            <h2 className="text-sm font-semibold text-[#313233]">Fotos do Pet</h2>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {report.pet.photos.map((photo) => (
                                    <img
                                        key={photo.id}
                                        src={photo.url}
                                        alt={report.pet?.name ?? 'Pet'}
                                        className="size-24 rounded-md object-cover"
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Report Sightings */}
                    <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                        <h2 className="text-sm font-semibold text-[#313233]">Avistamentos do Report ({report.report_sightings.length})</h2>
                        <div className="mt-4 flex flex-col gap-2.5">
                            {report.report_sightings.length > 0 ? (
                                report.report_sightings.map((rs) => (
                                    <div key={rs.id} className="flex items-center justify-between">
                                        <span className="text-[13px] font-medium text-[#313233]">Avistamento #{rs.id}</span>
                                        <span className="text-[11px] text-[#9B9C9D]">
                                            {new Date(rs.created_at).toLocaleDateString('pt-BR')}
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-[13px] text-[#9B9C9D]">Nenhum avistamento do report.</p>
                            )}
                        </div>
                    </div>

                    {/* Matches */}
                    <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                        <h2 className="text-sm font-semibold text-[#313233]">Matches ({report.matches.length})</h2>
                        <div className="mt-4 flex flex-col gap-2.5">
                            {report.matches.length > 0 ? (
                                report.matches.map((match) => (
                                    <div key={match.id} className="flex items-center justify-between">
                                        <Link href={`/admin/matches/${match.id}`} className="text-[13px] font-medium text-primary hover:underline">
                                            Match #{match.id}
                                            {match.sighting ? ` — ${match.sighting.title}` : ''}
                                        </Link>
                                        <div className="flex items-center gap-2">
                                            <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${matchStatusBadgeClass(match.status)}`}>
                                                {matchStatusLabels[match.status] ?? match.status}
                                            </span>
                                            {match.final_score && (
                                                <span className="text-[11px] text-[#9B9C9D]">
                                                    Score: {match.final_score}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-[13px] text-[#9B9C9D]">Nenhum match.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Cancel Dialog */}
            <AlertDialog open={showCancel} onOpenChange={(open) => { if (!open) { setShowCancel(false); setCancelReason(''); } }}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Cancelar report?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta ação cancelará o report. Informe o motivo abaixo.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="py-2">
                        <Label htmlFor="cancel-reason" className="text-sm font-medium text-[#313233]">Motivo</Label>
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

            {/* Found Dialog */}
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
