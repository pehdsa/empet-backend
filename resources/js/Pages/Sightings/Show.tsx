import { Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
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

function matchStatusBadgeClass(status: string): string {
    switch (status) {
        case 'PENDING': return 'bg-[#FEF3C7] text-[#92400E]';
        case 'CONFIRMED': return 'bg-[#DCFCE7] text-[#166534]';
        case 'DISMISSED': return 'bg-[#E7E8E5] text-[#6B6C6D]';
        default: return 'bg-[#E7E8E5] text-[#6B6C6D]';
    }
}

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
            {/* Title row */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link href="/admin/sightings" className="rounded p-1 hover:bg-[#F8F8F8]">
                        <ArrowLeft className="size-4 text-[#9B9C9D]" />
                    </Link>
                    <h1 className="text-[22px] font-bold text-[#313233]">{sighting.title}</h1>
                </div>
                <button onClick={() => setShowDelete(true)} className="flex items-center gap-1.5 rounded-lg border border-[#E53935] px-4 py-2 text-[13px] font-medium text-[#E53935] hover:bg-red-50">
                    <Trash2 className="size-3.5" />
                    Remover
                </button>
            </div>

            {/* Content grid */}
            <div className="grid gap-6 lg:grid-cols-2">
                {/* Info card */}
                <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                    <h2 className="text-sm font-semibold text-[#313233]">Informações</h2>
                    <div className="mt-4 flex flex-col gap-2.5">
                        <div className="flex justify-between">
                            <span className="text-[13px] text-[#9B9C9D]">Espécie</span>
                            <span className="text-[13px] font-medium text-[#313233]">{speciesLabels[sighting.species] ?? sighting.species}</span>
                        </div>
                        {sighting.breed && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Raça</span>
                                <span className="text-[13px] font-medium text-[#313233]">{sighting.breed.name}</span>
                            </div>
                        )}
                        {sighting.size && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Porte</span>
                                <span className="text-[13px] font-medium text-[#313233]">{sighting.size}</span>
                            </div>
                        )}
                        {sighting.sex && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Sexo</span>
                                <span className="text-[13px] font-medium text-[#313233]">{sighting.sex}</span>
                            </div>
                        )}
                        {sighting.color && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Cor</span>
                                <span className="text-[13px] font-medium text-[#313233]">{sighting.color}</span>
                            </div>
                        )}
                        {sighting.address_hint && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Local</span>
                                <span className="text-[13px] font-medium text-[#313233]">{sighting.address_hint}</span>
                            </div>
                        )}
                        <div className="border-t border-[#E2E2E2]" />
                        <div className="flex justify-between">
                            <span className="text-[13px] text-[#9B9C9D]">Reportado por</span>
                            <span className="text-[13px] font-medium text-[#313233]">{sighting.user?.name ?? '—'} {sighting.user?.email ? `(${sighting.user.email})` : ''}</span>
                        </div>
                        {sighting.sighted_at && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Avistado em</span>
                                <span className="text-[13px] font-medium text-[#313233]">{new Date(sighting.sighted_at).toLocaleDateString('pt-BR')}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-[13px] text-[#9B9C9D]">Criado em</span>
                            <span className="text-[13px] font-medium text-[#313233]">{new Date(sighting.created_at).toLocaleDateString('pt-BR')}</span>
                        </div>
                        {sighting.description && (
                            <>
                                <div className="border-t border-[#E2E2E2]" />
                                <p className="text-[13px] text-[#6B6C6D]">{sighting.description}</p>
                            </>
                        )}
                    </div>
                </div>

                <div className="flex flex-col gap-6">
                    {/* Characteristics */}
                    {sighting.characteristics.length > 0 && (
                        <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                            <h2 className="text-sm font-semibold text-[#313233]">Características</h2>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {sighting.characteristics.map((c) => (
                                    <span key={c.id} className="rounded-full border border-[#E2E2E2] px-2.5 py-0.5 text-[11px] font-medium text-[#313233]">
                                        {c.name}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Photos */}
                    {sighting.photos.length > 0 && (
                        <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                            <h2 className="text-sm font-semibold text-[#313233]">Fotos</h2>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {sighting.photos.map((photo) => (
                                    <img
                                        key={photo.id}
                                        src={photo.url}
                                        alt={sighting.title}
                                        className="size-24 rounded-md object-cover"
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Matches */}
                    <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                        <h2 className="text-sm font-semibold text-[#313233]">Matches ({sighting.matches.length})</h2>
                        <div className="mt-4 flex flex-col gap-2.5">
                            {sighting.matches.length > 0 ? (
                                sighting.matches.map((match) => (
                                    <div key={match.id} className="flex items-center justify-between">
                                        <Link href={`/admin/matches/${match.id}`} className="text-[13px] font-medium text-primary hover:underline">
                                            Match #{match.id}
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

            <AlertDialog open={showDelete} onOpenChange={(open) => { if (!open) { setShowDelete(false); setDeleteReason(''); } }}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Remover avistamento?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Confirma a remoção do avistamento "{sighting.title}"?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="py-2">
                        <Label htmlFor="delete-reason" className="text-sm font-medium text-[#313233]">Motivo (opcional)</Label>
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
