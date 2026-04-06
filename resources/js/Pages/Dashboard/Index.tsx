import { router, usePage } from '@inertiajs/react';
import {
    Users,
    PawPrint,
    FileSearch,
    CheckCircle,
    MapPin,
    GitCompare,
} from 'lucide-react';
import { useEffect } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    XAxis,
    YAxis,
} from 'recharts';

import AdminLayout from '@/Layouts/AdminLayout';
import type { SharedProps } from '@/Types/inertia';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

interface TimelineEntry {
    date: string;
    count: number;
}

interface MatchStatusEntry {
    status: string;
    count: number;
}

interface DashboardProps extends SharedProps {
    kpis: KPIs;
    latestReports: LatestReport[];
    latestSightings: LatestSighting[];
    reportsTimeline?: TimelineEntry[];
    matchesByStatus?: MatchStatusEntry[];
}

const kpiCards = [
    { key: 'users' as const, label: 'Usuários', icon: Users, color: 'text-blue-600' },
    { key: 'pets' as const, label: 'Pets ativos', icon: PawPrint, color: 'text-empet-primary' },
    { key: 'reportsOpen' as const, label: 'Reports abertos', icon: FileSearch, color: 'text-red-600' },
    { key: 'reportsFound' as const, label: 'Encontrados', icon: CheckCircle, color: 'text-empet-success' },
    { key: 'sightings' as const, label: 'Avistamentos', icon: MapPin, color: 'text-empet-secondary' },
    { key: 'matchesPending' as const, label: 'Matches pendentes', icon: GitCompare, color: 'text-yellow-600' },
];

const statusColors: Record<string, string> = {
    LOST: 'destructive',
    FOUND: 'default',
    CANCELLED: 'secondary',
};

const matchStatusColors: Record<string, string> = {
    PENDING: 'hsl(45, 100%, 50%)',
    CONFIRMED: 'hsl(135, 50%, 40%)',
    DISMISSED: 'hsl(0, 0%, 60%)',
};

const timelineConfig: ChartConfig = {
    count: { label: 'Reports', color: 'var(--color-empet-primary)' },
};

const matchChartConfig: ChartConfig = {
    PENDING: { label: 'Pendente', color: matchStatusColors.PENDING },
    CONFIRMED: { label: 'Confirmado', color: matchStatusColors.CONFIRMED },
    DISMISSED: { label: 'Descartado', color: matchStatusColors.DISMISSED },
};

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
    });
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
            {/* KPI Cards */}
            <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                {kpiCards.map(({ key, label, icon: Icon, color }) => (
                    <Card key={key}>
                        <CardContent className="flex items-center gap-3 p-4">
                            <Icon className={`size-8 ${color}`} />
                            <div>
                                <p className="text-2xl font-bold">{kpis[key]}</p>
                                <p className="text-xs text-muted-foreground">{label}</p>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Charts */}
            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Reports (últimos 30 dias)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {reportsTimeline && reportsTimeline.length > 0 ? (
                            <ChartContainer config={timelineConfig} className="h-[250px] w-full">
                                <BarChart data={reportsTimeline}>
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="date"
                                        tickFormatter={(v) => formatDate(v)}
                                        fontSize={11}
                                    />
                                    <YAxis allowDecimals={false} fontSize={11} />
                                    <ChartTooltip content={<ChartTooltipContent />} />
                                    <Bar dataKey="count" fill="var(--color-count)" radius={4} />
                                </BarChart>
                            </ChartContainer>
                        ) : (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                {reportsTimeline ? 'Sem dados no período.' : 'Carregando...'}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Matches por status</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {matchesByStatus && matchesByStatus.length > 0 ? (
                            <ChartContainer config={matchChartConfig} className="mx-auto h-[250px] w-full max-w-[300px]">
                                <PieChart>
                                    <ChartTooltip content={<ChartTooltipContent />} />
                                    <Pie
                                        data={matchesByStatus}
                                        dataKey="count"
                                        nameKey="status"
                                        innerRadius={50}
                                        outerRadius={90}
                                        strokeWidth={2}
                                    >
                                        {matchesByStatus.map((entry) => (
                                            <Cell
                                                key={entry.status}
                                                fill={matchStatusColors[entry.status] ?? 'hsl(0, 0%, 80%)'}
                                            />
                                        ))}
                                    </Pie>
                                </PieChart>
                            </ChartContainer>
                        ) : (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                {matchesByStatus ? 'Sem dados.' : 'Carregando...'}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Latest lists */}
            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Últimos reports</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {latestReports.length > 0 ? (
                            latestReports.map((report) => (
                                <div key={report.id} className="flex items-center justify-between text-sm">
                                    <div>
                                        <span className="font-medium">
                                            {report.petName ?? 'Pet desconhecido'}
                                        </span>
                                        <span className="ml-2 text-muted-foreground">
                                            por {report.ownerName}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge variant={statusColors[report.status] as any ?? 'secondary'}>
                                            {report.status}
                                        </Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {formatDate(report.createdAt)}
                                        </span>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                Nenhum report encontrado.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Últimos avistamentos</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {latestSightings.length > 0 ? (
                            latestSightings.map((sighting) => (
                                <div key={sighting.id} className="flex items-center justify-between text-sm">
                                    <div>
                                        <span className="font-medium">{sighting.title}</span>
                                        <span className="ml-2 text-muted-foreground">
                                            por {sighting.reporterName}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge variant="outline">{sighting.species}</Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {formatDate(sighting.sightedAt)}
                                        </span>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                Nenhum avistamento encontrado.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
