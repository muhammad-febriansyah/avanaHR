import { Head } from '@inertiajs/react';
import { CalendarPlus, UserMinus, Users, UserCheck } from 'lucide-react';
import PageHeader from '@/components/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Bucket = { label: string; total: number };

type WorkforceProps = {
    kpis: {
        total: number;
        active: number;
        hires_this_month: number;
        separations_this_month: number;
    };
    byStatus: Bucket[];
    byGender: Bucket[];
    byDepartment: Bucket[];
    byType: Bucket[];
    hireTrend: Bucket[];
};

const STATUS_LABELS: Record<string, string> = {
    active: 'Aktif',
    probation: 'Probation',
    inactive: 'Nonaktif',
    on_leave: 'Cuti',
    suspended: 'Ditangguhkan',
    resigned: 'Resign',
    terminated: 'Terminasi',
};

const GENDER_LABELS: Record<string, string> = {
    male: 'Laki-laki',
    female: 'Perempuan',
};

const TYPE_LABELS: Record<string, string> = {
    permanent: 'Tetap',
    contract: 'Kontrak',
    probation: 'Probation',
    intern: 'Magang',
    outsource: 'Outsource',
};

function relabel(map: Record<string, string>, items: Bucket[]): Bucket[] {
    return items.map((item) => ({
        ...item,
        label: map[item.label] ?? item.label,
    }));
}

function BarList({ items, accent }: { items: Bucket[]; accent: string }) {
    const max = Math.max(1, ...items.map((item) => item.total));

    if (items.length === 0) {
        return (
            <p className="py-6 text-center text-sm text-muted-foreground">
                Belum ada data
            </p>
        );
    }

    return (
        <div className="flex flex-col gap-3">
            {items.map((item) => (
                <div key={item.label} className="flex flex-col gap-1">
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-foreground">{item.label}</span>
                        <span className="font-medium tabular-nums">{item.total}</span>
                    </div>
                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            className={`h-full rounded-full ${accent}`}
                            style={{ width: `${(item.total / max) * 100}%` }}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}

const KPIS: Array<{
    key: keyof WorkforceProps['kpis'];
    label: string;
    icon: typeof Users;
    accent: string;
}> = [
    { key: 'total', label: 'Total Karyawan', icon: Users, accent: 'bg-primary/10 text-primary' },
    { key: 'active', label: 'Aktif', icon: UserCheck, accent: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' },
    { key: 'hires_this_month', label: 'Hire Bulan Ini', icon: CalendarPlus, accent: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400' },
    { key: 'separations_this_month', label: 'Keluar Bulan Ini', icon: UserMinus, accent: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400' },
];

export default function WorkforceAnalytics({
    kpis,
    byStatus,
    byGender,
    byDepartment,
    byType,
    hireTrend,
}: WorkforceProps) {
    return (
        <>
            <Head title="Analitik Workforce" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Analitik Workforce"
                    description="Ringkasan komposisi & pergerakan tenaga kerja."
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {KPIS.map((kpi) => (
                        <Card key={kpi.key}>
                            <CardContent className="flex items-center justify-between p-5">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        {kpi.label}
                                    </p>
                                    <p className="text-2xl font-semibold text-navy tabular-nums">
                                        {kpis[kpi.key]}
                                    </p>
                                </div>
                                <span
                                    className={`flex size-10 items-center justify-center rounded-lg ${kpi.accent}`}
                                >
                                    <kpi.icon className="size-5" />
                                </span>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Tren Rekrutmen (6 Bulan)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-end gap-3" style={{ height: 160 }}>
                            {hireTrend.map((month) => {
                                const max = Math.max(
                                    1,
                                    ...hireTrend.map((item) => item.total),
                                );

                                return (
                                    <div
                                        key={month.label}
                                        className="flex flex-1 flex-col items-center gap-2"
                                    >
                                        <span className="text-xs font-medium tabular-nums">
                                            {month.total}
                                        </span>
                                        <div className="flex w-full flex-1 items-end">
                                            <div
                                                className="w-full rounded-t bg-primary"
                                                style={{
                                                    height: `${(month.total / max) * 100}%`,
                                                    minHeight: month.total > 0 ? 4 : 0,
                                                }}
                                            />
                                        </div>
                                        <span className="text-xs text-muted-foreground">
                                            {month.label}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Headcount per Departemen
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarList items={byDepartment} accent="bg-primary" />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Status Karyawan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarList
                                items={relabel(STATUS_LABELS, byStatus)}
                                accent="bg-emerald-500"
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Jenis Kelamin</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarList
                                items={relabel(GENDER_LABELS, byGender)}
                                accent="bg-sky-500"
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Tipe Kerja</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarList
                                items={relabel(TYPE_LABELS, byType)}
                                accent="bg-violet-500"
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
