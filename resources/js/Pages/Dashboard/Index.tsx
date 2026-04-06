import { router, usePage } from '@inertiajs/react';
import {
    Users,
    PawPrint,
    FileSearch,
    CircleCheck,
    MapPin,
    GitCompare,
} from 'lucide-react';
import { useEffect } from 'react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';

interface KPIs {
    users: number;
    pets: number;
    reportsOpen: number;
    reportsFound: number;
    sightings: number;
    matchesPending: number;
}

interface LatestReport {
    id: number;
    status: string;
    petName: string | null;
    petSpecies: string | null;
    ownerName: string;
    createdAt: string;
}

interface LatestSighting {
    id: number;
    title: string;
    species: string;
    reporterName: string;
    sightedAt: string;
}

interface TimelineEntry { date: string; count: number }
interface MatchStatusEntry { status: string; count: number }

interface DashboardProps extends SharedProps {
    kpis: KPIs;
    latestReports: LatestReport[];
    latestSightings: LatestSighting[];
    reportsTimeline?: TimelineEntry[];
    matchesByStatus?: MatchStatusEntry[];
}

const kpiCards = [
    { key: 'users' as const, label: 'Usuários', icon: Users, color: '#2563EB' },
    { key: 'pets' as const, label: 'Pets ativos', icon: PawPrint, color: '#FFA001' },
    { key: 'reportsOpen' as const, label: 'Reports abertos', icon: FileSearch, color: '#E53935' },
    { key: 'reportsFound' as const, label: 'Encontrados', icon: CircleCheck, color: '#43A047' },
    { key: 'sightings' as const, label: 'Avistamentos', icon: MapPin, color: '#AD4FFF' },
    { key: 'matchesPending' as const, label: 'Matches pendentes', icon: GitCompare, color: '#CA8A04' },
];

const statusBadge: Record<string, { bg: string; text: string }> = {
    LOST: { bg: '#E53935', text: '#FFFFFF' },
    FOUND: { bg: '#43A047', text: '#FFFFFF' },
    CANCELLED: { bg: '#E7E8E5', text: '#6B6C6D' },
};

const timelineConfig: ChartConfig = {
    count: { label: 'Reports', color: '#FFA001' },
};

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
}

export default function DashboardIndex() {
    const { kpis, latestReports, latestSightings, reportsTimeline, matchesByStatus } =
        usePage<DashboardProps>().props;

    useEffect(() => {
        if (!reportsTimeline || !matchesByStatus) {
            router.reload({ only: ['reportsTimeline', 'matchesByStatus'] });
        }
    }, []);

    return (
        <AdminLayout breadcrumbs={[{ label: 'Dashboard' }]}>
            <h1 className="text-[22px] font-bold text-[#313233]">Dashboard</h1>

            {/* KPI Cards — gap-4 (16px) */}
            <div className="grid grid-cols-6 gap-4">
                {kpiCards.map(({ key, label, icon: Icon, color }) => (
                    <div
                        key={key}
                        className="flex items-center gap-3 rounded-[10px] border border-[#E2E2E2] bg-white p-4"
                    >
                        <Icon className="size-7 shrink-0" style={{ color }} />
                        <div className="flex flex-col gap-0.5">
                            <span className="text-[22px] font-bold leading-tight text-[#313233]">{kpis[key]}</span>
                            <span className="text-[11px] text-[#9B9C9D]">{label}</span>
                        </div>
                    </div>
                ))}
            </div>

            {/* Charts — gap-4 (16px) */}
            <div className="grid grid-cols-2 gap-4">
                {/* Bar chart */}
                <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                    <h2 className="text-sm font-semibold text-[#313233]">Reports (últimos 30 dias)</h2>
                    <div className="mt-4">
                        {reportsTimeline && reportsTimeline.length > 0 ? (
                            <ChartContainer config={timelineConfig} className="h-40 w-full">
                                <BarChart data={reportsTimeline}>
                                    <CartesianGrid vertical={false} />
                                    <XAxis dataKey="date" tickFormatter={formatDate} fontSize={11} />
                                    <YAxis allowDecimals={false} fontSize={11} />
                                    <ChartTooltip content={<ChartTooltipContent />} />
                                    <Bar dataKey="count" fill="var(--color-count)" radius={4} />
                                </BarChart>
                            </ChartContainer>
                        ) : (
                            <p className="py-10 text-center text-[13px] text-[#9B9C9D]">
                                {reportsTimeline ? 'Sem dados no período.' : 'Carregando...'}
                            </p>
                        )}
                    </div>
                </div>

                {/* Donut chart */}
                <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                    <h2 className="text-sm font-semibold text-[#313233]">Matches por status</h2>
                    <div className="mt-4 flex flex-col items-center gap-4">
                        {matchesByStatus && matchesByStatus.length > 0 ? (
                            <>
                                <div className="flex size-36 items-center justify-center rounded-full border-[16px] border-[#FFA001] bg-white">
                                    <span className="text-lg font-bold text-[#313233]">
                                        {matchesByStatus.reduce((s, e) => s + e.count, 0)}
                                    </span>
                                </div>
                                <div className="flex gap-4">
                                    {[
                                        { label: 'Pendente', color: '#EAB308' },
                                        { label: 'Confirmado', color: '#43A047' },
                                        { label: 'Descartado', color: '#9CA3AF' },
                                    ].map((item) => (
                                        <div key={item.label} className="flex items-center gap-1">
                                            <div className="size-2 rounded-full" style={{ backgroundColor: item.color }} />
                                            <span className="text-[11px] text-[#6B6C6D]">{item.label}</span>
                                        </div>
                                    ))}
                                </div>
                            </>
                        ) : (
                            <p className="py-10 text-[13px] text-[#9B9C9D]">
                                {matchesByStatus ? 'Sem dados.' : 'Carregando...'}
                            </p>
                        )}
                    </div>
                </div>
            </div>

            {/* Latest lists — gap-4 */}
            <div className="grid grid-cols-2 gap-4">
                <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                    <h2 className="text-sm font-semibold text-[#313233]">Últimos reports</h2>
                    <div className="mt-3 flex flex-col gap-3">
                        {latestReports.length > 0 ? latestReports.map((r) => (
                            <div key={r.id} className="flex items-center justify-between">
                                <div className="flex gap-1">
                                    <span className="text-[13px] font-medium text-[#313233]">{r.petName ?? 'Pet desconhecido'}</span>
                                    <span className="text-[13px] text-[#9B9C9D]">por {r.ownerName}</span>
                                </div>
                                <span
                                    className="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    style={{ backgroundColor: statusBadge[r.status]?.bg, color: statusBadge[r.status]?.text }}
                                >
                                    {r.status}
                                </span>
                            </div>
                        )) : (
                            <p className="py-4 text-center text-[13px] text-[#9B9C9D]">Nenhum report.</p>
                        )}
                    </div>
                </div>

                <div className="rounded-[10px] border border-[#E2E2E2] bg-white p-5">
                    <h2 className="text-sm font-semibold text-[#313233]">Últimos avistamentos</h2>
                    <div className="mt-3 flex flex-col gap-3">
                        {latestSightings.length > 0 ? latestSightings.map((s) => (
                            <div key={s.id} className="flex items-center justify-between">
                                <div className="flex gap-1">
                                    <span className="text-[13px] font-medium text-[#313233]">{s.title}</span>
                                    <span className="text-[13px] text-[#9B9C9D]">por {s.reporterName}</span>
                                </div>
                                <span className="rounded-full bg-[#E7E8E5] px-2 py-0.5 text-[10px] font-semibold text-[#313233]">
                                    {s.species}
                                </span>
                            </div>
                        )) : (
                            <p className="py-4 text-center text-[13px] text-[#9B9C9D]">Nenhum avistamento.</p>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
