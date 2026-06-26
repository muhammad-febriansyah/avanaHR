import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Eye, LifeBuoy, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import hrTickets from '@/actions/App/Http/Controllers/HrTicketController';
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

type Option = { value: string; label: string };

type TicketRow = {
    id: number;
    ticket_no: string;
    subject: string;
    category: string;
    status: string;
    priority: string;
    employee_name: string | null;
    employee_no: string | null;
    messages_count: number;
    created_at: string | null;
};

type Filters = { search?: string; status?: string; priority?: string };

type IndexProps = {
    tickets: Paginator<TicketRow>;
    filters: Filters;
    statuses: Option[];
    priorities: Option[];
    categories: Option[];
};

const ALL = 'all';

export const STATUS_STYLES: Record<string, string> = {
    open: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    in_progress:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    resolved:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    closed: 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
};

export const PRIORITY_STYLES: Record<string, string> = {
    low: 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
    medium: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    high: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    urgent: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

function label(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

export default function HrTicketsIndex({
    tickets: paginator,
    filters = {},
    statuses,
    priorities,
    categories,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? ALL);
    const [priority, setPriority] = useState(filters.priority ?? ALL);
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            hrTickets.index.url(),
            {
                search: search || undefined,
                status: status === ALL ? undefined : status,
                priority: priority === ALL ? undefined : priority,
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
            <Head title="Tiket HR" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tiket HR"
                    description="Helpdesk internal: pertanyaan & permintaan karyawan ke tim HR."
                >
                    <Button asChild>
                        <Link href={hrTickets.create.url()}>
                            <Plus />
                            Buat Tiket
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari subjek / no tiket"
                                className="sm:max-w-xs"
                            />
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
                                <SelectTrigger className="w-full sm:w-44">
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
                            <Select
                                value={priority}
                                onValueChange={(value) => {
                                    setPriority(value);
                                    go({
                                        priority:
                                            value === ALL ? undefined : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="Semua Prioritas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>
                                        Semua Prioritas
                                    </SelectItem>
                                    {priorities.map((option) => (
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
                                        <TableHead>No. Tiket</TableHead>
                                        <TableHead>Subjek</TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Prioritas</TableHead>
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
                                                colSpan={7}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <LifeBuoy className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada tiket
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
                                                <TableCell className="font-mono text-xs">
                                                    {row.ticket_no}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {row.subject}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {label(
                                                            categories,
                                                            row.category,
                                                        )}{' '}
                                                        · {row.messages_count}{' '}
                                                        pesan
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm">
                                                        {row.employee_name ??
                                                            '-'}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {row.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={
                                                            PRIORITY_STYLES[
                                                                row.priority
                                                            ]
                                                        }
                                                    >
                                                        {label(
                                                            priorities,
                                                            row.priority,
                                                        )}
                                                    </Badge>
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
                                                        {label(
                                                            statuses,
                                                            row.status,
                                                        )}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="warning"
                                                    >
                                                        <Link
                                                            href={hrTickets.show.url(
                                                                row.id,
                                                            )}
                                                        >
                                                            <Eye />
                                                            Lihat
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
                                {paginator.total} tiket
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
