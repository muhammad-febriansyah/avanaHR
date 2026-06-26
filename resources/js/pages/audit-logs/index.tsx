import { Head, router } from '@inertiajs/react';
import { ScrollText, Search } from 'lucide-react';
import auditLogs from '@/actions/App/Http/Controllers/AuditLogController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
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

type Change = { field: string; old: string | null; new: string | null };

type LogRow = {
    id: number;
    event: string | null;
    entity: string | null;
    entity_id: number | null;
    user_name: string | null;
    user_email: string | null;
    ip: string | null;
    changes: Change[];
    created_at: string | null;
};

type Option = { value: string; label: string };
type Paginated<T> = { data: T[] };

type IndexProps = {
    logs: Paginated<LogRow>;
    filters: { search?: string; event?: string };
    events: Option[];
};

export default function AuditLogsIndex({ logs, filters, events }: IndexProps) {
    function apply(key: string, value: string) {
        router.get(
            auditLogs.index.url(),
            { ...filters, [key]: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Audit Log" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Audit Log"
                    description="Jejak perubahan data sensitif di perusahaan Anda."
                />

                <Card className="gap-0 py-0">
                    <CardContent className="grid gap-3 p-5 md:grid-cols-3">
                        <div className="relative md:col-span-2">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                defaultValue={filters.search ?? ''}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter')
                                        apply(
                                            'search',
                                            (e.target as HTMLInputElement)
                                                .value,
                                        );
                                }}
                                placeholder="Cari user / event / entitas…"
                                className="pl-10"
                            />
                        </div>
                        <Select
                            value={filters.event ?? ''}
                            onValueChange={(v) =>
                                apply('event', v === 'all' ? '' : v)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Semua event" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua event</SelectItem>
                                {events.map((e) => (
                                    <SelectItem key={e.value} value={e.value}>
                                        {e.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </CardContent>
                </Card>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Waktu</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Entitas</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>IP</TableHead>
                                    <TableHead>Perubahan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            <ScrollText className="mx-auto mb-2 size-6 opacity-50" />
                                            Belum ada catatan audit
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    logs.data.map((log) => (
                                        <TableRow key={log.id}>
                                            <TableCell className="text-xs whitespace-nowrap text-muted-foreground tabular-nums">
                                                {log.created_at}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {log.event}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {log.entity}
                                                {log.entity_id
                                                    ? ` #${log.entity_id}`
                                                    : ''}
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {log.user_name ?? '—'}
                                                <span className="block text-xs text-muted-foreground">
                                                    {log.user_email}
                                                </span>
                                            </TableCell>
                                            <TableCell className="font-mono text-xs text-muted-foreground">
                                                {log.ip ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                {log.changes.length === 0 ? (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                ) : (
                                                    log.changes
                                                        .slice(0, 4)
                                                        .map((c) => (
                                                            <div key={c.field}>
                                                                <span className="font-medium">
                                                                    {c.field}:
                                                                </span>{' '}
                                                                <span className="text-muted-foreground line-through">
                                                                    {c.old ??
                                                                        '∅'}
                                                                </span>{' '}
                                                                →{' '}
                                                                <span className="text-navy">
                                                                    {c.new ??
                                                                        '∅'}
                                                                </span>
                                                            </div>
                                                        ))
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
