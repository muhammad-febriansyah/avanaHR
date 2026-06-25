import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, ShieldAlert } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import securityEvents from '@/actions/App/Http/Controllers/Platform/SecurityEventController';
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
import type { Paginator } from '@/types/employee';

type TypeOption = { value: string; label: string };
type MetaPair = { key: string; value: string };

type EventRow = {
    id: number;
    type: string;
    user_name: string | null;
    user_email: string | null;
    tenant_name: string | null;
    ip: string | null;
    user_agent: string | null;
    meta: MetaPair[];
    created_at: string | null;
};

type Filters = { search?: string; type?: string };

type IndexProps = {
    events: Paginator<EventRow>;
    filters: Filters;
    types: TypeOption[];
};

const ALL_TYPE = 'all';

const DANGER = 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400';
const WARN = 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400';
const OK = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400';

const TYPE_STYLES: Record<string, string> = {
    login_success: OK,
    two_factor_enabled: OK,
    password_changed: WARN,
    login_failed: WARN,
    locked_out: DANGER,
    suspicious_login: DANGER,
};

export default function SecurityEventsIndex({
    events: paginator,
    filters = {},
    types,
}: IndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? ALL_TYPE);
    const firstRender = useRef(true);

    function typeLabel(value: string): string {
        return types.find((option) => option.value === value)?.label ?? value;
    }

    function go(extra: Record<string, string | undefined>) {
        router.get(
            securityEvents.index.url(),
            {
                search: search || undefined,
                type: type === ALL_TYPE ? undefined : type,
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
            <Head title="Security Events" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Security Events"
                    description="Peristiwa keamanan lintas tenant: login gagal, akun terkunci, perubahan sandi, 2FA."
                />

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari user, tipe, atau IP"
                                className="sm:max-w-xs"
                            />
                            <Select
                                value={type}
                                onValueChange={(value) => {
                                    setType(value);
                                    go({
                                        type:
                                            value === ALL_TYPE ? undefined : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-56">
                                    <SelectValue placeholder="Semua Tipe" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_TYPE}>
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
                        </div>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Tenant</TableHead>
                                        <TableHead>IP</TableHead>
                                        <TableHead>Detail</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <ShieldAlert className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada peristiwa keamanan
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
                                                    <Badge
                                                        variant="secondary"
                                                        className={TYPE_STYLES[row.type] ?? WARN}
                                                    >
                                                        {typeLabel(row.type)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {row.user_name ?? 'Anonim'}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {row.user_email}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {row.tenant_name ?? '-'}
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground tabular-nums">
                                                    {row.ip ?? '-'}
                                                </TableCell>
                                                <TableCell className="max-w-[18rem]">
                                                    {row.meta.length === 0 ? (
                                                        <span className="text-xs text-muted-foreground">
                                                            -
                                                        </span>
                                                    ) : (
                                                        <div className="flex flex-wrap gap-1">
                                                            {row.meta.map((pair) => (
                                                                <span
                                                                    key={pair.key}
                                                                    className="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
                                                                >
                                                                    {pair.key}: {pair.value}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} peristiwa
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
