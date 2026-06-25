import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, ScrollText } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import auditLogs from '@/actions/App/Http/Controllers/Platform/AuditLogController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import type { Paginator } from '@/types/employee';

type EventOption = { value: string; label: string };
type Change = { field: string; old: string | null; new: string | null };

type AuditRow = {
    id: number;
    event: string;
    entity: string | null;
    entity_id: number | null;
    user_name: string | null;
    user_email: string | null;
    tenant_name: string | null;
    ip: string | null;
    changes: Change[];
    created_at: string | null;
};

type Filters = { search?: string; event?: string };

type IndexProps = {
    logs: Paginator<AuditRow>;
    filters: Filters;
    events: EventOption[];
};

const ALL_EVENT = 'all';

const EVENT_STYLES: Record<string, string> = {
    created: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    updated: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    deleted: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

const EVENT_LABELS: Record<string, string> = {
    created: 'Dibuat',
    updated: 'Diubah',
    deleted: 'Dihapus',
};

export default function AuditLogsIndex({
    logs: paginator,
    filters = {},
    events,
}: IndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [event, setEvent] = useState(filters.event ?? ALL_EVENT);
    const firstRender = useRef(true);

    const [detail, setDetail] = useState<AuditRow | null>(null);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            auditLogs.index.url(),
            {
                search: search || undefined,
                event: event === ALL_EVENT ? undefined : event,
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
            <Head title="Audit Log" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Audit Log"
                    description="Jejak perubahan data sensitif lintas tenant (actor, aksi, before/after)."
                />

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari user, entitas, atau aksi"
                                className="sm:max-w-xs"
                            />
                            <Select
                                value={event}
                                onValueChange={(value) => {
                                    setEvent(value);
                                    go({
                                        event:
                                            value === ALL_EVENT ? undefined : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-48">
                                    <SelectValue placeholder="Semua Aksi" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_EVENT}>
                                        Semua Aksi
                                    </SelectItem>
                                    {events.map((option) => (
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
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Aksi</TableHead>
                                        <TableHead>Entitas</TableHead>
                                        <TableHead>Tenant</TableHead>
                                        <TableHead>IP</TableHead>
                                        <TableHead className="text-right">
                                            Detail
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <ScrollText className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada catatan audit
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="whitespace-nowrap text-xs text-muted-foreground tabular-nums">
                                                    {row.created_at}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {row.user_name ?? 'Sistem'}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {row.user_email}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={EVENT_STYLES[row.event]}
                                                    >
                                                        {EVENT_LABELS[row.event] ??
                                                            row.event}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-medium">
                                                        {row.entity ?? '-'}
                                                    </span>
                                                    {row.entity_id ? (
                                                        <span className="text-xs text-muted-foreground">
                                                            {' '}
                                                            #{row.entity_id}
                                                        </span>
                                                    ) : null}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {row.tenant_name ?? '-'}
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground tabular-nums">
                                                    {row.ip ?? '-'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={row.changes.length === 0}
                                                        onClick={() => setDetail(row)}
                                                    >
                                                        {row.changes.length} perubahan
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
                                {paginator.total} catatan
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

            <Dialog open={!!detail} onOpenChange={(open) => !open && setDetail(null)}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            Perubahan — {detail?.entity}
                            {detail?.entity_id ? ` #${detail.entity_id}` : ''}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="max-h-[60vh] overflow-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Field</TableHead>
                                    <TableHead>Sebelum</TableHead>
                                    <TableHead>Sesudah</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {detail?.changes.map((change) => (
                                    <TableRow key={change.field}>
                                        <TableCell className="font-medium">
                                            {change.field}
                                        </TableCell>
                                        <TableCell className="text-red-700 dark:text-red-400">
                                            {change.old ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-emerald-700 dark:text-emerald-400">
                                            {change.new ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
