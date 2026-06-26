import { Head, router } from '@inertiajs/react';
import { CalendarClock, ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import attendance from '@/actions/App/Http/Controllers/AttendanceController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import type { Paginator } from '@/types/employee';

type StatusOption = { value: string; label: string };

type AttendanceRow = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    date: string | null;
    clock_in: string | null;
    clock_out: string | null;
    work_minutes: number;
    late_minutes: number;
    status: string;
    has_correction: boolean;
};

type Filters = {
    search?: string;
    status?: string;
    from?: string;
    to?: string;
};

type IndexProps = {
    rows: Paginator<AttendanceRow>;
    filters: Filters;
    statuses: StatusOption[];
};

const ALL_STATUS = 'all';

const STATUS_STYLES: Record<string, string> = {
    present:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    late: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    absent: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    leave: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    holiday:
        'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
    off: 'bg-muted text-muted-foreground',
};

function statusLabel(statuses: StatusOption[], value: string): string {
    return statuses.find((option) => option.value === value)?.label ?? value;
}

function formatHours(minutes: number): string {
    if (!minutes) {
        return '-';
    }

    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    return mins === 0 ? `${hours} jam` : `${hours} jam ${mins} mnt`;
}

export default function AttendanceIndex({
    rows: paginator,
    filters = {},
    statuses,
}: IndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? ALL_STATUS);
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            attendance.index.url(),
            {
                search: search || undefined,
                status: status === ALL_STATUS ? undefined : status,
                from: from || undefined,
                to: to || undefined,
                ...extra,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = setTimeout(() => go({}), 350);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search, from, to]);

    const rows = paginator.data;

    return (
        <>
            <Head title="Rekap Absensi" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Rekap Absensi"
                    description="Pantau kehadiran harian karyawan: jam masuk/keluar, keterlambatan, dan koreksi."
                />

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="grid gap-1.5">
                                <Label htmlFor="att-from">Dari Tanggal</Label>
                                <DatePicker
                                    id="att-from"
                                    value={from}
                                    onChange={(value) => setFrom(value)}
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="att-to">Sampai Tanggal</Label>
                                <DatePicker
                                    id="att-to"
                                    value={to}
                                    onChange={(value) => setTo(value)}
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="att-status">Status</Label>
                                <Select
                                    value={status}
                                    onValueChange={(value) => {
                                        setStatus(value);
                                        go({
                                            status:
                                                value === ALL_STATUS
                                                    ? undefined
                                                    : value,
                                        });
                                    }}
                                >
                                    <SelectTrigger
                                        id="att-status"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Semua Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL_STATUS}>
                                            Semua Status
                                        </SelectItem>
                                        {statuses.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="att-search">
                                    Cari Karyawan
                                </Label>
                                <Input
                                    id="att-search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Nama / NIP"
                                />
                            </div>
                        </div>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Masuk</TableHead>
                                        <TableHead>Keluar</TableHead>
                                        <TableHead>Telat</TableHead>
                                        <TableHead>Jam Kerja</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={8}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <CalendarClock className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Tidak ada data absensi
                                                        pada rentang ini
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((row, index) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="text-muted-foreground tabular-nums">
                                                    {(paginator.from ?? 1) +
                                                        index}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2 font-medium">
                                                        {row.employee_name}
                                                        {row.has_correction ? (
                                                            <Badge
                                                                variant="outline"
                                                                className="text-[10px]"
                                                            >
                                                                koreksi
                                                            </Badge>
                                                        ) : null}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {row.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {row.date}
                                                </TableCell>
                                                <TableCell>
                                                    {row.clock_in ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {row.clock_out ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {row.late_minutes
                                                        ? `${row.late_minutes} mnt`
                                                        : '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {formatHours(
                                                        row.work_minutes,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={
                                                            STATUS_STYLES[
                                                                row.status
                                                            ]
                                                        }
                                                    >
                                                        {statusLabel(
                                                            statuses,
                                                            row.status,
                                                        )}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} baris
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
