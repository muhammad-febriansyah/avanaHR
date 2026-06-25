import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, CreditCard, Layers } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import subscriptions from '@/actions/App/Http/Controllers/Platform/SubscriptionController';
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

type TierOption = { value: string; label: string };

type SubRow = {
    id: number;
    name: string;
    slug: string;
    tier: string | null;
    status: string | null;
    features_enabled: number;
    features_total: number;
    starts_at: string | null;
    ends_at: string | null;
};

type Filters = { search?: string; tier?: string };

type IndexProps = {
    tenants: Paginator<SubRow>;
    filters: Filters;
    tiers: TierOption[];
    summary: Record<string, number>;
};

const ALL_TIER = 'all';

const TIER_STYLES: Record<string, string> = {
    essential: 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
    professional: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    enterprise: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
};

function cap(value: string | null): string {
    return value ? value.charAt(0).toUpperCase() + value.slice(1) : '-';
}

export default function SubscriptionsIndex({
    tenants: paginator,
    filters = {},
    tiers,
    summary,
}: IndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [tier, setTier] = useState(filters.tier ?? ALL_TIER);
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            subscriptions.index.url(),
            {
                search: search || undefined,
                tier: tier === ALL_TIER ? undefined : tier,
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
            <Head title="Langganan & Paket" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Langganan & Paket"
                    description="Distribusi tier langganan tenant beserta fitur yang aktif."
                />

                <div className="grid gap-4 sm:grid-cols-3">
                    {tiers.map((option) => (
                        <Card key={option.value}>
                            <CardContent className="flex items-center justify-between p-5">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        {option.label}
                                    </p>
                                    <p className="text-2xl font-semibold text-navy tabular-nums">
                                        {summary[option.value] ?? 0}
                                    </p>
                                </div>
                                <span
                                    className={`flex size-10 items-center justify-center rounded-lg ${TIER_STYLES[option.value]}`}
                                >
                                    <Layers className="size-5" />
                                </span>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari tenant"
                                className="sm:max-w-xs"
                            />
                            <Select
                                value={tier}
                                onValueChange={(value) => {
                                    setTier(value);
                                    go({
                                        tier:
                                            value === ALL_TIER ? undefined : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-48">
                                    <SelectValue placeholder="Semua Tier" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_TIER}>
                                        Semua Tier
                                    </SelectItem>
                                    {tiers.map((option) => (
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
                                        <TableHead>Tenant</TableHead>
                                        <TableHead>Tier</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Fitur Aktif</TableHead>
                                        <TableHead>Mulai</TableHead>
                                        <TableHead>Berakhir</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <CreditCard className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada langganan
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {row.name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {row.slug}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {row.tier ? (
                                                        <Badge
                                                            variant="secondary"
                                                            className={TIER_STYLES[row.tier]}
                                                        >
                                                            {cap(row.tier)}
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            -
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {cap(row.status)}
                                                </TableCell>
                                                <TableCell className="tabular-nums">
                                                    {row.features_enabled}/
                                                    {row.features_total}
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground tabular-nums">
                                                    {row.starts_at ?? '-'}
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground tabular-nums">
                                                    {row.ends_at ?? 'Tanpa batas'}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} tenant
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
