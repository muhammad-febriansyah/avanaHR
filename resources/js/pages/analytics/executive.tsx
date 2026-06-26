import { Head } from '@inertiajs/react';
import { Banknote, ClipboardList, TrendingDown, Users } from 'lucide-react';
import PageHeader from '@/components/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatRupiah } from '@/lib/format';

type Bucket = { label: string; total: number };

type ExecutiveProps = {
    kpis: {
        headcount: number;
        turnover_rate: number;
        pending_total: number;
        payroll_net: number;
    };
    pending: Bucket[];
    payroll: {
        run_no: string | null;
        gross: number;
        net: number;
        tax: number;
        bpjs: number;
    };
    reimbursementPending: number;
    byDepartment: Bucket[];
    byType: Bucket[];
};

const TYPE_LABELS: Record<string, string> = {
    permanent: 'Tetap',
    contract: 'Kontrak',
    probation: 'Probation',
    intern: 'Magang',
    outsource: 'Outsource',
};

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
                        <span>{TYPE_LABELS[item.label] ?? item.label}</span>
                        <span className="font-medium tabular-nums">
                            {item.total}
                        </span>
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

export default function ExecutiveDashboard({
    kpis,
    pending,
    payroll,
    reimbursementPending,
    byDepartment,
    byType,
}: ExecutiveProps) {
    const kpiCards = [
        {
            label: 'Headcount Aktif',
            value: String(kpis.headcount),
            icon: Users,
            accent: 'bg-primary/10 text-primary',
        },
        {
            label: 'Turnover (12 bln)',
            value: `${kpis.turnover_rate}%`,
            icon: TrendingDown,
            accent: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
        },
        {
            label: 'Menunggu Persetujuan',
            value: String(kpis.pending_total),
            icon: ClipboardList,
            accent: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
        },
        {
            label: 'Payroll Bersih Terakhir',
            value: formatRupiah(kpis.payroll_net),
            icon: Banknote,
            accent: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
        },
    ];

    const payrollRows = [
        { label: 'Bruto', value: payroll.gross },
        { label: 'Pajak (PPh 21)', value: payroll.tax },
        { label: 'BPJS', value: payroll.bpjs },
        { label: 'Netto', value: payroll.net },
    ];

    return (
        <>
            <Head title="Dashboard Eksekutif" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Dashboard Eksekutif"
                    description="Ringkasan strategis lintas modul untuk manajemen & BOD."
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {kpiCards.map((card) => (
                        <Card key={card.label}>
                            <CardContent className="flex items-center justify-between p-5">
                                <div className="min-w-0">
                                    <p className="text-sm text-muted-foreground">
                                        {card.label}
                                    </p>
                                    <p className="truncate text-2xl font-semibold text-navy tabular-nums">
                                        {card.value}
                                    </p>
                                </div>
                                <span
                                    className={`flex size-10 shrink-0 items-center justify-center rounded-lg ${card.accent}`}
                                >
                                    <card.icon className="size-5" />
                                </span>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Komposisi Payroll Terakhir
                                {payroll.run_no ? (
                                    <span className="ml-2 text-xs font-normal text-muted-foreground">
                                        {payroll.run_no}
                                    </span>
                                ) : null}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {payroll.net === 0 && payroll.gross === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    Belum ada payroll run
                                </p>
                            ) : (
                                <div className="flex flex-col divide-y">
                                    {payrollRows.map((row) => (
                                        <div
                                            key={row.label}
                                            className="flex items-center justify-between py-2.5 text-sm"
                                        >
                                            <span className="text-muted-foreground">
                                                {row.label}
                                            </span>
                                            <span className="font-medium tabular-nums">
                                                {formatRupiah(row.value)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Antrian Persetujuan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <BarList items={pending} accent="bg-sky-500" />
                            <div className="flex items-center justify-between rounded-lg border bg-muted/30 px-3 py-2.5 text-sm">
                                <span className="text-muted-foreground">
                                    Nilai reimbursement tertunda
                                </span>
                                <span className="font-medium tabular-nums">
                                    {formatRupiah(reimbursementPending)}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

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
                            <CardTitle className="text-base">
                                Tipe Kerja
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarList items={byType} accent="bg-violet-500" />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
