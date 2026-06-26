import { Head, Link, router } from '@inertiajs/react';
import {
    Building2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
    ChevronUp,
    Eye,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import tenants from '@/actions/App/Http/Controllers/Platform/TenantController';
import ConfirmDialog from '@/components/confirm-dialog';
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
import { formatDateID } from '@/lib/format';
import type {
    Paginator,
    StatusOption,
    TenantFilters,
    TenantRow,
} from '@/types/tenant';

const ALL_STATUSES = 'all';
const PER_PAGE_OPTIONS = ['15', '25', '50', '100'];
const DEFAULT_PER_PAGE = '15';

type IndexProps = {
    tenants: Paginator<TenantRow>;
    filters: TenantFilters;
    statuses: StatusOption[];
};

const STATUS_PILL: Record<string, { label: string; className: string }> = {
    active: {
        label: 'Aktif',
        className: 'bg-emerald-100 text-emerald-700',
    },
    suspended: {
        label: 'Ditangguhkan',
        className: 'bg-amber-100 text-amber-700',
    },
    inactive: {
        label: 'Nonaktif',
        className: 'bg-muted text-muted-foreground',
    },
};

function TenantStatusPill({ status }: { status: string }) {
    const pill = STATUS_PILL[status] ?? {
        label: status,
        className: 'bg-muted text-muted-foreground',
    };

    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${pill.className}`}
        >
            {pill.label}
        </span>
    );
}

function capitalize(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

export default function TenantsIndex({
    tenants: paginator,
    filters = {},
    statuses = [],
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? ALL_STATUSES);
    const [sort, setSort] = useState(filters.sort ?? '');
    const [dir, setDir] = useState<'asc' | 'desc'>(filters.dir ?? 'desc');
    const [perPage, setPerPage] = useState(
        String(filters.per_page ?? DEFAULT_PER_PAGE),
    );
    const isFirstRender = useRef(true);

    function buildQuery(extra: Record<string, unknown> = {}) {
        return {
            search: search || undefined,
            status: status === ALL_STATUSES ? undefined : status,
            sort: sort || undefined,
            dir: sort ? dir : undefined,
            per_page: perPage === DEFAULT_PER_PAGE ? undefined : perPage,
            ...extra,
        };
    }

    function go(extra: Record<string, unknown> = {}) {
        router.get(tenants.index.url(), buildQuery(extra), {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        });
    }

    // Debounced search + status filter.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => go(), 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search, status]);

    function toggleSort(column: string) {
        const nextDir = sort === column && dir === 'asc' ? 'desc' : 'asc';
        setSort(column);
        setDir(nextDir);
        go({ sort: column, dir: nextDir });
    }

    function changePerPage(value: string) {
        setPerPage(value);
        go({ per_page: value === DEFAULT_PER_PAGE ? undefined : value });
    }

    function handleDelete(id: number) {
        router.delete(tenants.destroy.url(id), { preserveScroll: true });
    }

    function sortHeader(label: string, column: string) {
        const active = sort === column;

        return (
            <TableHead>
                <button
                    type="button"
                    onClick={() => toggleSort(column)}
                    className="inline-flex items-center gap-1.5 transition-colors hover:text-foreground"
                >
                    {label}
                    {active ? (
                        dir === 'asc' ? (
                            <ChevronUp className="size-3.5" />
                        ) : (
                            <ChevronDown className="size-3.5" />
                        )
                    ) : (
                        <ChevronsUpDown className="size-3.5 opacity-40" />
                    )}
                </button>
            </TableHead>
        );
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Tenant" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tenant"
                    description="Kelola seluruh tenant pada platform."
                >
                    <Button asChild>
                        <Link href={tenants.create.url()}>
                            <Plus />
                            Tambah Tenant
                        </Link>
                    </Button>
                </PageHeader>

                {/* Stats chip */}
                <div className="flex flex-wrap gap-2">
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                        Total
                        <span className="tabular-nums">{paginator.total}</span>
                    </span>
                </div>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-wrap items-center gap-3">
                            <div className="relative w-full sm:max-w-xs">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Cari nama atau slug..."
                                    className="pl-9"
                                />
                            </div>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger className="w-full sm:w-48">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_STATUSES}>
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

                        <div className="overflow-x-auto rounded-lg border border-border/60">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        {sortHeader('Nama', 'name')}
                                        <TableHead>Paket</TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        {sortHeader('Status', 'status')}
                                        {sortHeader('Dibuat', 'created_at')}
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
                                                        <Building2 className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada tenant
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((tenant, index) => (
                                            <TableRow key={tenant.id}>
                                                <TableCell className="text-muted-foreground tabular-nums">
                                                    {(paginator.from ?? 1) +
                                                        index}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-col">
                                                        <span className="font-medium whitespace-nowrap">
                                                            {tenant.name}
                                                        </span>
                                                        <span className="text-xs text-muted-foreground">
                                                            {tenant.slug}
                                                        </span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {tenant.subscription?.tier
                                                        ? capitalize(
                                                              tenant
                                                                  .subscription
                                                                  .tier,
                                                          )
                                                        : '-'}
                                                </TableCell>
                                                <TableCell className="tabular-nums">
                                                    {tenant.employees_count}
                                                </TableCell>
                                                <TableCell>
                                                    <TenantStatusPill
                                                        status={tenant.status}
                                                    />
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap tabular-nums">
                                                    {formatDateID(
                                                        tenant.created_at,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="warning"
                                                        >
                                                            <Link
                                                                href={tenants.show.url(
                                                                    tenant.id,
                                                                )}
                                                            >
                                                                <Eye />
                                                                Lihat
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="success"
                                                        >
                                                            <Link
                                                                href={tenants.edit.url(
                                                                    tenant.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Tenant"
                                                            description={`Yakin ingin menghapus ${tenant.name}? Tindakan ini tidak dapat dibatalkan.`}
                                                            confirmLabel="Hapus"
                                                            onConfirm={() =>
                                                                handleDelete(
                                                                    tenant.id,
                                                                )
                                                            }
                                                            trigger={
                                                                <Button
                                                                    size="sm"
                                                                    variant="destructive"
                                                                >
                                                                    <Trash2 />
                                                                    Hapus
                                                                </Button>
                                                            }
                                                        />
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <p className="text-sm text-muted-foreground">
                                    {paginator.total > 0
                                        ? `Menampilkan ${paginator.from ?? 0}–${paginator.to ?? 0} dari ${paginator.total}`
                                        : 'Menampilkan 0 dari 0'}
                                </p>
                                <Select
                                    value={perPage}
                                    onValueChange={changePerPage}
                                >
                                    <SelectTrigger
                                        size="sm"
                                        className="w-[88px]"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {PER_PAGE_OPTIONS.map((option) => (
                                            <SelectItem
                                                key={option}
                                                value={option}
                                            >
                                                {option} / hal
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={!paginator.prev_page_url}
                                    onClick={() => {
                                        if (paginator.prev_page_url) {
                                            router.get(
                                                paginator.prev_page_url,
                                                {},
                                                {
                                                    preserveState: true,
                                                    preserveScroll: true,
                                                },
                                            );
                                        }
                                    }}
                                >
                                    <ChevronLeft />
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={!paginator.next_page_url}
                                    onClick={() => {
                                        if (paginator.next_page_url) {
                                            router.get(
                                                paginator.next_page_url,
                                                {},
                                                {
                                                    preserveState: true,
                                                    preserveScroll: true,
                                                },
                                            );
                                        }
                                    }}
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
