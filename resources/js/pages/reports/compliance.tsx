import { Head, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    FileSpreadsheet,
    Landmark,
    Receipt,
    ShieldCheck,
} from 'lucide-react';
import reports from '@/actions/App/Http/Controllers/ComplianceReportController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatRupiah } from '@/lib/format';
import type { Paginator } from '@/types/employee';

type RunRow = {
    id: number;
    run_no: string | null;
    period_code: string | null;
    period_label: string | null;
    status: string;
    employees: number;
    gross: number;
    pph21: number;
    bpjs_employee: number;
    bpjs_company: number;
    bpjs_total: number;
};

type IndexProps = {
    runs: Paginator<RunRow>;
    year: number;
    years: number[];
    summary: {
        runs: number;
        pph21: number;
        bpjs: number;
        gross: number;
    };
};

export default function ComplianceReport({
    runs: paginator,
    year,
    years,
    summary,
}: IndexProps) {
    function setYear(value: string) {
        router.get(
            reports.index.url(),
            { year: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const cards = [
        {
            label: 'Total PPh 21',
            value: formatRupiah(summary.pph21),
            icon: Receipt,
            accent: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
        },
        {
            label: 'Total BPJS',
            value: formatRupiah(summary.bpjs),
            icon: ShieldCheck,
            accent: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
        },
        {
            label: 'Total Bruto',
            value: formatRupiah(summary.gross),
            icon: Landmark,
            accent: 'bg-primary/10 text-primary',
        },
        {
            label: 'Payroll Run',
            value: String(summary.runs),
            icon: FileSpreadsheet,
            accent: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
        },
    ];

    const rows = paginator.data;

    return (
        <>
            <Head title="Laporan Kepatuhan" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Laporan Kepatuhan"
                    description="Rekap PPh 21 & BPJS per payroll run untuk pelaporan pajak & jaminan sosial."
                >
                    <Select value={String(year)} onValueChange={setYear}>
                        <SelectTrigger className="w-32">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {years.map((option) => (
                                <SelectItem key={option} value={String(option)}>
                                    {option}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </PageHeader>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {cards.map((card) => (
                        <Card key={card.label}>
                            <CardContent className="flex items-center justify-between p-5">
                                <div className="min-w-0">
                                    <p className="text-sm text-muted-foreground">
                                        {card.label}
                                    </p>
                                    <p className="truncate text-xl font-semibold text-navy tabular-nums">
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

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="overflow-x-auto rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Periode</TableHead>
                                        <TableHead>Run</TableHead>
                                        <TableHead className="text-right">Karyawan</TableHead>
                                        <TableHead className="text-right">Bruto</TableHead>
                                        <TableHead className="text-right">PPh 21</TableHead>
                                        <TableHead className="text-right">
                                            BPJS Karyawan
                                        </TableHead>
                                        <TableHead className="text-right">
                                            BPJS Perusahaan
                                        </TableHead>
                                        <TableHead className="text-right">
                                            BPJS Total
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <FileSpreadsheet className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada payroll run di {year}
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {row.period_label ??
                                                            row.period_code}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-xs">
                                                            {row.run_no}
                                                        </span>
                                                        <Badge
                                                            variant="secondary"
                                                            className="text-[10px]"
                                                        >
                                                            {row.status}
                                                        </Badge>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {row.employees}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {formatRupiah(row.gross)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {formatRupiah(row.pph21)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {formatRupiah(row.bpjs_employee)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {formatRupiah(row.bpjs_company)}
                                                </TableCell>
                                                <TableCell className="text-right font-medium tabular-nums">
                                                    {formatRupiah(row.bpjs_total)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} run
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!paginator.prev_page_url}
                                    onClick={() =>
                                        paginator.prev_page_url &&
                                        router.get(
                                            paginator.prev_page_url,
                                            {},
                                            { preserveState: true, preserveScroll: true },
                                        )
                                    }
                                >
                                    <ChevronLeft />
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!paginator.next_page_url}
                                    onClick={() =>
                                        paginator.next_page_url &&
                                        router.get(
                                            paginator.next_page_url,
                                            {},
                                            { preserveState: true, preserveScroll: true },
                                        )
                                    }
                                >
                                    Berikutnya
                                    <ChevronRight />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
