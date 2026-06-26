import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, PencilRuler } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import attendanceCorrections from '@/actions/App/Http/Controllers/AttendanceCorrectionController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
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

type StatusOption = { value: string; label: string };

type CorrectionRow = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    date: string | null;
    requested_in: string | null;
    requested_out: string | null;
    reason: string | null;
    status: string;
};

type Filters = { search?: string; status?: string };

type IndexProps = {
    corrections: Paginator<CorrectionRow>;
    filters: Filters;
    statuses: StatusOption[];
};

const ALL_STATUS = 'all';

const STATUS_STYLES: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

const STATUS_LABELS: Record<string, string> = {
    pending: 'Pending',
    approved: 'Disetujui',
    rejected: 'Ditolak',
};

export default function AttendanceCorrectionsIndex({
    corrections: paginator,
    filters = {},
    statuses,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? ALL_STATUS);
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            attendanceCorrections.index.url(),
            {
                search: search || undefined,
                status: status === ALL_STATUS ? undefined : status,
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
    }, [search]);

    const rows = paginator.data;

    return (
        <>
            <Head title="Koreksi Absensi" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Koreksi Absensi"
                    description="Tinjau pengajuan koreksi kehadiran. Persetujuan dilakukan melalui Inbox Approval."
                />

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Cari nama / NIP karyawan"
                                className="sm:max-w-xs"
                            />
                            <Select
                                value={status}
                                onValueChange={(value) => {
                                    setStatus(value);
                                    go({
                                        status:
                                            value === ALL_STATUS ? undefined : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-48">
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

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Usulan Masuk</TableHead>
                                        <TableHead>Usulan Keluar</TableHead>
                                        <TableHead>Alasan</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <PencilRuler className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada pengajuan koreksi
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {item.employee_name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell>{item.date}</TableCell>
                                                <TableCell>
                                                    {item.requested_in ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {item.requested_out ?? '-'}
                                                </TableCell>
                                                <TableCell className="max-w-[16rem] truncate text-muted-foreground">
                                                    {item.reason}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={STATUS_STYLES[item.status]}
                                                    >
                                                        {STATUS_LABELS[item.status] ??
                                                            item.status}
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
                                {paginator.total} pengajuan
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
