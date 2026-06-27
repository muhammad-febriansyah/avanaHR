import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Eye, Plus, Shuffle } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import movements from '@/actions/App/Http/Controllers/EmployeeMovementController';
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
import { formatDateID } from '@/lib/format';
import type { Paginator } from '@/types/employee';

type Option = { value: string; label: string };

type MovementRow = {
    id: number;
    employee_name: string;
    employee_no: string;
    type: string;
    type_label: string;
    effective_date: string;
    status: string;
    status_label: string;
    applied_at: string | null;
};

type Filters = { search?: string; type?: string; status?: string };

type IndexProps = {
    movements: Paginator<MovementRow>;
    filters: Filters;
    types: Option[];
    statuses: Option[];
};

const ALL = 'all';

const TYPE_STYLES: Record<string, string> = {
    promotion: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    demotion:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    transfer:
        'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
    mutation:
        'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
    suspension:
        'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400',
    exit: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
    scheduled:
        'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-400',
    applied:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

export default function MovementsIndex({
    movements: paginator,
    filters = {},
    types,
    statuses,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? ALL);
    const [status, setStatus] = useState(filters.status ?? ALL);
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            movements.index.url(),
            {
                search: search || undefined,
                type: type === ALL ? undefined : type,
                status: status === ALL ? undefined : status,
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
            <Head title="Mutasi & Movement" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Mutasi & Movement"
                    description="Kelola mutasi, promosi, demosi, skorsing, dan exit karyawan."
                >
                    <Button asChild>
                        <Link href={movements.create.url()}>
                            <Plus />
                            Buat Movement
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama / NIP"
                                className="sm:max-w-xs"
                            />
                            <Select
                                value={type}
                                onValueChange={(value) => {
                                    setType(value);
                                    go({
                                        type: value === ALL ? undefined : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-48">
                                    <SelectValue placeholder="Semua Tipe" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>
                                        Semua Tipe
                                    </SelectItem>
                                    {types.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={status}
                                onValueChange={(value) => {
                                    setStatus(value);
                                    go({
                                        status:
                                            value === ALL ? undefined : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-48">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>
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
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Tanggal Efektif</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <Shuffle className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada movement
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
                                                    <Badge
                                                        variant="secondary"
                                                        className={
                                                            TYPE_STYLES[
                                                                item.type
                                                            ]
                                                        }
                                                    >
                                                        {item.type_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="tabular-nums">
                                                    {formatDateID(
                                                        item.effective_date,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={
                                                            STATUS_STYLES[
                                                                item.status
                                                            ]
                                                        }
                                                    >
                                                        {item.status_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="warning"
                                                    >
                                                        <Link
                                                            href={movements.show.url(
                                                                item.id,
                                                            )}
                                                        >
                                                            <Eye />
                                                            Detail
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} movement
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
