import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Eye, FileText } from 'lucide-react';
import { useState } from 'react';
import payslips from '@/actions/App/Http/Controllers/PayslipController';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
import { useFlashToast } from '@/hooks/use-flash-toast';
import type { Paginator } from '@/types/employee';

type RunOption = { id: number; run_no: string };

type Row = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    run_no: string | null;
    period_code: string | null;
    gross: number;
    net: number;
};

type Filters = { search?: string; run_id?: string };

type IndexProps = {
    payslips: Paginator<Row>;
    filters: Filters;
    options: { runs: RunOption[] };
};

const ALL_RUN = 'all';

const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
});

export default function PayslipsIndex({
    payslips: paginator,
    filters = {},
    options,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [runId, setRunId] = useState(filters.run_id ?? ALL_RUN);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            payslips.index.url(),
            {
                search: search || undefined,
                run_id: runId === ALL_RUN ? undefined : runId,
                ...extra,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Slip Gaji" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Slip Gaji"
                    description="Lihat slip gaji karyawan hasil proses payroll."
                />

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter') {
                                        go({});
                                    }
                                }}
                                placeholder="Cari nama / NIP, lalu Enter"
                                className="sm:max-w-xs"
                            />
                            <Select
                                value={runId}
                                onValueChange={(value) => {
                                    setRunId(value);
                                    go({
                                        run_id:
                                            value === ALL_RUN
                                                ? undefined
                                                : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-56">
                                    <SelectValue placeholder="Semua Run" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_RUN}>
                                        Semua Run
                                    </SelectItem>
                                    {options.runs.map((run) => (
                                        <SelectItem
                                            key={run.id}
                                            value={String(run.id)}
                                        >
                                            {run.run_no}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Run</TableHead>
                                        <TableHead>Periode</TableHead>
                                        <TableHead className="text-right">
                                            Gross
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Net
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <FileText className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada slip gaji
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item, index) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="text-muted-foreground tabular-nums">
                                                    {(paginator.from ?? 1) +
                                                        index}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {item.employee_name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {item.run_no}
                                                </TableCell>
                                                <TableCell>
                                                    {item.period_code}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {rupiah.format(item.gross)}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {rupiah.format(item.net)}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end">
                                                        <Button
                                                            size="sm"
                                                            variant="warning"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={payslips.show.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Eye />
                                                                Lihat
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} slip
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={!paginator.prev_page_url}
                                    onClick={() =>
                                        paginator.prev_page_url &&
                                        router.get(
                                            paginator.prev_page_url,
                                            {},
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
                                        )
                                    }
                                >
                                    <ChevronLeft />
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={!paginator.next_page_url}
                                    onClick={() =>
                                        paginator.next_page_url &&
                                        router.get(
                                            paginator.next_page_url,
                                            {},
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
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
