import { Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, XCircle } from 'lucide-react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
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

function matchStatusBadgeClass(status: string): string {
    switch (status) {
        case 'PENDING': return 'bg-[#FEF3C7] text-[#92400E]';
        case 'CONFIRMED': return 'bg-[#DCFCE7] text-[#166534]';
        case 'DISMISSED': return 'bg-[#E7E8E5] text-[#6B6C6D]';
        default: return 'bg-[#E7E8E5] text-[#6B6C6D]';
    }
}

const reportStatusLabels: Record<string, string> = {
    LOST: 'Perdido',
    FOUND: 'Encontrado',
    CANCELLED: 'Cancelado',
};

function reportStatusBadgeClass(status: string): string {
    switch (status) {
        case 'LOST': return 'bg-[#E53935] text-white';
        case 'FOUND': return 'bg-[#43A047] text-white';
        case 'CANCELLED': return 'bg-[#E7E8E5] text-[#6B6C6D]';
        default: return 'bg-[#E7E8E5] text-[#6B6C6D]';
    }
}

const aiStatusLabels: Record<string, string> = {
    PENDING: 'Pendente',
    PROCESSING: 'Processando',
    COMPLETED: 'Completo',
    FAILED: 'Falhou',
    SKIPPED: 'Ignorado',
};

function aiStatusBadgeClass(status: string | null): string {
    switch (status) {
        case 'COMPLETED': return 'bg-[#DCFCE7] text-[#166534]';
        case 'FAILED': return 'bg-[#E53935] text-white';
        case 'PROCESSING': return 'bg-[#FEF3C7] text-[#92400E]';
        case 'PENDING': return 'bg-[#FEF3C7] text-[#92400E]';
        case 'SKIPPED': return 'bg-[#E7E8E5] text-[#6B6C6D]';
        default: return 'bg-[#E7E8E5] text-[#6B6C6D]';
    }
}

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
            {/* Title row */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link href="/admin/matches" className="rounded p-1 hover:bg-[#F8F8F8]">
                        <ArrowLeft className="size-4 text-[#9B9C9D]" />
                    </Link>
                    <h1 className="text-[22px] font-bold text-[#313233]">Match #{match.id}</h1>
                    <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${matchStatusBadgeClass(match.status)}`}>
                        {matchStatusLabels[match.status] ?? match.status}
                    </span>
                </div>
                {match.status === 'PENDING' && (
                    <button onClick={() => setShowDismiss(true)} className="flex items-center gap-1.5 rounded-lg border border-[#E53935] px-4 py-2 text-[13px] font-medium text-[#E53935] hover:bg-red-50">
                        <XCircle className="size-3.5" />
                        Descartar
                    </button>
                )}
            </div>

            {/* Split layout: Report vs Sighting */}
            <div className="grid gap-6 lg:grid-cols-2">
                {/* Left: Report info */}
                <div className="flex flex-col gap-6">
                    <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                        <h2 className="text-sm font-semibold text-[#313233]">Report</h2>
                        <div className="mt-4 flex flex-col gap-2.5">
                            {match.report ? (
                                <>
                                    <div className="flex justify-between">
                                        <span className="text-[13px] text-[#9B9C9D]">Report</span>
                                        <Link href={`/admin/reports/${match.report.id}`} className="text-[13px] font-medium text-primary hover:underline">
                                            #{match.report.id}
                                        </Link>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-[13px] text-[#9B9C9D]">Status</span>
                                        <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${reportStatusBadgeClass(match.report.status)}`}>
                                            {reportStatusLabels[match.report.status] ?? match.report.status}
                                        </span>
                                    </div>
                                    {match.report.pet && (
                                        <div className="flex justify-between">
                                            <span className="text-[13px] text-[#9B9C9D]">Pet</span>
                                            <Link href={`/admin/pets/${match.report.pet.id}`} className="text-[13px] font-medium text-primary hover:underline">
                                                {match.report.pet.name}
                                            </Link>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <span className="text-[13px] text-[#9B9C9D]">Dono</span>
                                        <span className="text-[13px] font-medium text-[#313233]">{match.report.user?.name ?? '—'}</span>
                                    </div>
                                    {match.report.description && (
                                        <>
                                            <div className="border-t border-[#E2E2E2]" />
                                            <p className="text-[13px] text-[#6B6C6D]">{match.report.description}</p>
                                        </>
                                    )}
                                </>
                            ) : (
                                <p className="text-[13px] text-[#9B9C9D]">Report não disponível.</p>
                            )}
                        </div>
                    </div>

                    {match.report?.pet && match.report.pet.photos.length > 0 && (
                        <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                            <h2 className="text-sm font-semibold text-[#313233]">Fotos do Pet</h2>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {match.report.pet.photos.map((photo) => (
                                    <img
                                        key={photo.id}
                                        src={photo.url}
                                        alt={match.report?.pet?.name ?? 'Pet'}
                                        className="size-24 rounded-md object-cover"
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {/* Right: Sighting info */}
                <div className="flex flex-col gap-6">
                    <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                        <h2 className="text-sm font-semibold text-[#313233]">Avistamento</h2>
                        <div className="mt-4 flex flex-col gap-2.5">
                            {match.sighting ? (
                                <>
                                    <div className="flex justify-between">
                                        <span className="text-[13px] text-[#9B9C9D]">Avistamento</span>
                                        <Link href={`/admin/sightings/${match.sighting.id}`} className="text-[13px] font-medium text-primary hover:underline">
                                            {match.sighting.title}
                                        </Link>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-[13px] text-[#9B9C9D]">Reportado por</span>
                                        <span className="text-[13px] font-medium text-[#313233]">{match.sighting.user?.name ?? '—'}</span>
                                    </div>
                                    {match.sighting.address_hint && (
                                        <div className="flex justify-between">
                                            <span className="text-[13px] text-[#9B9C9D]">Local</span>
                                            <span className="text-[13px] font-medium text-[#313233]">{match.sighting.address_hint}</span>
                                        </div>
                                    )}
                                    {match.sighting.description && (
                                        <>
                                            <div className="border-t border-[#E2E2E2]" />
                                            <p className="text-[13px] text-[#6B6C6D]">{match.sighting.description}</p>
                                        </>
                                    )}
                                </>
                            ) : (
                                <p className="text-[13px] text-[#9B9C9D]">Avistamento não disponível.</p>
                            )}
                        </div>
                    </div>

                    {match.sighting && match.sighting.photos.length > 0 && (
                        <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                            <h2 className="text-sm font-semibold text-[#313233]">Fotos do Avistamento</h2>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {match.sighting.photos.map((photo) => (
                                    <img
                                        key={photo.id}
                                        src={photo.url}
                                        alt={match.sighting?.title ?? 'Avistamento'}
                                        className="size-24 rounded-md object-cover"
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* AI Info Card - full width */}
            <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                <h2 className="text-sm font-semibold text-[#313233]">Informações da IA</h2>
                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <span className="text-[13px] text-[#9B9C9D]">Status IA</span>
                        <div className="mt-1">
                            <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${aiStatusBadgeClass(match.ai_status)}`}>
                                {match.ai_status ? (aiStatusLabels[match.ai_status] ?? match.ai_status) : '—'}
                            </span>
                        </div>
                    </div>
                    <div>
                        <span className="text-[13px] text-[#9B9C9D]">Score IA</span>
                        <p className="mt-1 text-[13px] font-medium text-[#313233]">{match.ai_score ?? '—'}</p>
                    </div>
                    <div>
                        <span className="text-[13px] text-[#9B9C9D]">Confiança IA</span>
                        <p className="mt-1 text-[13px] font-medium text-[#313233]">{match.ai_confidence ?? '—'}</p>
                    </div>
                    <div>
                        <span className="text-[13px] text-[#9B9C9D]">Score Final</span>
                        <p className="mt-1 text-[13px] font-medium text-[#313233]">{match.final_score ?? '—'}</p>
                    </div>
                </div>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <span className="text-[13px] text-[#9B9C9D]">Score Base</span>
                        <p className="mt-1 text-[13px] font-medium text-[#313233]">{match.base_score ?? '—'}</p>
                    </div>
                    <div>
                        <span className="text-[13px] text-[#9B9C9D]">Distância</span>
                        <p className="mt-1 text-[13px] font-medium text-[#313233]">
                            {match.distance_meters ? `${Number(match.distance_meters).toFixed(0)}m` : '—'}
                        </p>
                    </div>
                </div>
                {match.ai_summary && (
                    <div className="mt-4">
                        <span className="text-[13px] text-[#9B9C9D]">Resumo IA</span>
                        <p className="mt-1 rounded-lg bg-[#F8F8F8] p-3 text-[13px] text-[#6B6C6D]">{match.ai_summary}</p>
                    </div>
                )}
            </div>

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
