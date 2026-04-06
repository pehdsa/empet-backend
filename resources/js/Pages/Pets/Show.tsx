import { Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Power } from 'lucide-react';
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

function statusBadgeClass(status: string): string {
    switch (status) {
        case 'LOST': return 'bg-[#E53935] text-white';
        case 'FOUND': return 'bg-[#43A047] text-white';
        case 'CANCELLED': return 'bg-[#E7E8E5] text-[#6B6C6D]';
        default: return 'bg-[#E7E8E5] text-[#6B6C6D]';
    }
}

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
            {/* Title row */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link href="/admin/pets" className="rounded p-1 hover:bg-[#F8F8F8]">
                        <ArrowLeft className="size-4 text-[#9B9C9D]" />
                    </Link>
                    <h1 className="text-[22px] font-bold text-[#313233]">{pet.name}</h1>
                    <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                        pet.is_active ? 'bg-[#43A047] text-white' : 'bg-[#E7E8E5] text-[#6B6C6D]'
                    }`}>
                        {pet.is_active ? 'Ativo' : 'Inativo'}
                    </span>
                </div>
                <button
                    onClick={() => setShowConfirm(true)}
                    className={`flex items-center gap-1.5 rounded-lg px-4 py-2 text-[13px] font-medium ${
                        pet.is_active
                            ? 'border border-[#E53935] text-[#E53935] hover:bg-red-50'
                            : 'bg-primary text-white hover:bg-[#CC8000]'
                    }`}
                >
                    <Power className="size-3.5" />
                    {pet.is_active ? 'Desativar' : 'Reativar'}
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
                            <span className="text-[13px] font-medium text-[#313233]">{speciesLabels[pet.species] ?? pet.species}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-[13px] text-[#9B9C9D]">Raça</span>
                            <span className="text-[13px] font-medium text-[#313233]">{pet.breed?.name ?? '—'}{pet.secondary_breed ? ` / ${pet.secondary_breed.name}` : ''}</span>
                        </div>
                        {pet.size && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Porte</span>
                                <span className="text-[13px] font-medium text-[#313233]">{pet.size}</span>
                            </div>
                        )}
                        {pet.sex && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Sexo</span>
                                <span className="text-[13px] font-medium text-[#313233]">{pet.sex}</span>
                            </div>
                        )}
                        {pet.primary_color && (
                            <div className="flex justify-between">
                                <span className="text-[13px] text-[#9B9C9D]">Cor</span>
                                <span className="text-[13px] font-medium text-[#313233]">{pet.primary_color}</span>
                            </div>
                        )}
                        <div className="border-t border-[#E2E2E2]" />
                        <div className="flex justify-between">
                            <span className="text-[13px] text-[#9B9C9D]">Dono</span>
                            <span className="text-[13px] font-medium text-[#313233]">{pet.user?.name ?? '—'} ({pet.user?.email})</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-[13px] text-[#9B9C9D]">Cadastrado em</span>
                            <span className="text-[13px] font-medium text-[#313233]">{new Date(pet.created_at).toLocaleDateString('pt-BR')}</span>
                        </div>
                        {pet.notes && (
                            <>
                                <div className="border-t border-[#E2E2E2]" />
                                <p className="text-[13px] text-[#6B6C6D]">{pet.notes}</p>
                            </>
                        )}
                    </div>
                </div>

                <div className="flex flex-col gap-6">
                    {/* Characteristics */}
                    {pet.characteristics.length > 0 && (
                        <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                            <h2 className="text-sm font-semibold text-[#313233]">Características</h2>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {pet.characteristics.map((c) => (
                                    <span key={c.id} className="rounded-full border border-[#E2E2E2] px-2.5 py-0.5 text-[11px] font-medium text-[#313233]">
                                        {c.name}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Photos */}
                    {pet.photos.length > 0 && (
                        <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                            <h2 className="text-sm font-semibold text-[#313233]">Fotos</h2>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {pet.photos.map((photo) => (
                                    <img
                                        key={photo.id}
                                        src={photo.url}
                                        alt={pet.name}
                                        className="size-24 rounded-md object-cover"
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Reports */}
                    <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                        <h2 className="text-sm font-semibold text-[#313233]">Reports ({pet.reports.length})</h2>
                        <div className="mt-4 flex flex-col gap-2.5">
                            {pet.reports.length > 0 ? (
                                pet.reports.map((report) => (
                                    <div key={report.id} className="flex items-center justify-between">
                                        <Link href={`/admin/reports/${report.id}`} className="text-[13px] font-medium text-primary hover:underline">
                                            Report #{report.id}
                                        </Link>
                                        <div className="flex items-center gap-2">
                                            <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold ${statusBadgeClass(report.status)}`}>
                                                {statusLabels[report.status] ?? report.status}
                                            </span>
                                            <span className="text-[11px] text-[#9B9C9D]">
                                                {new Date(report.created_at).toLocaleDateString('pt-BR')}
                                            </span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-[13px] text-[#9B9C9D]">Nenhum report.</p>
                            )}
                        </div>
                    </div>
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
