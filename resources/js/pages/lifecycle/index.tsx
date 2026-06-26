import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    ChevronLeft,
    ChevronRight,
    History,
    Plus,
    Trash2,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import lifecycle from '@/actions/App/Http/Controllers/EmployeeLifecycleEventController';
import ConfirmDialog from '@/components/confirm-dialog';
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

type EventRow = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    type: string;
    effective_date: string | null;
    from_value: string | null;
    to_value: string | null;
    reason: string | null;
};

type Filters = { search?: string; type?: string };

type IndexProps = {
    events: Paginator<EventRow>;
    filters: Filters;
    types: Option[];
};

const ALL = 'all';

const TYPE_STYLES: Record<string, string> = {
    hire: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    promotion: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    transfer:
        'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
    demotion:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    contract_change:
        'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
    resign: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    terminate: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    reactivate:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
};

function label(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

export default function LifecycleIndex({
    events: paginator,
    filters = {},
    types,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? ALL);
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            lifecycle.index.url(),
            {
                search: search || undefined,
                type: type === ALL ? undefined : type,
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

    function handleDelete(id: number) {
        router.delete(lifecycle.destroy.url(id), { preserveScroll: true });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Lifecycle" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Lifecycle Karyawan"
                    description="Riwayat peristiwa karier: bergabung, promosi, mutasi, hingga keluar."
                >
                    <Button asChild>
                        <Link href={lifecycle.create.url()}>
                            <Plus />
                            Catat Peristiwa
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
                                    <SelectValue placeholder="Semua Jenis" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>
                                        Semua Jenis
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
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Peristiwa</TableHead>
                                        <TableHead>Tanggal Efektif</TableHead>
                                        <TableHead>Perubahan</TableHead>
                                        <TableHead>Alasan</TableHead>
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
                                                        <History className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada peristiwa
                                                        lifecycle
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
                                                        {label(
                                                            types,
                                                            item.type,
                                                        )}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="tabular-nums">
                                                    {item.effective_date}
                                                </TableCell>
                                                <TableCell>
                                                    {item.from_value ||
                                                    item.to_value ? (
                                                        <span className="flex items-center gap-1.5 text-sm">
                                                            <span className="text-muted-foreground">
                                                                {item.from_value ??
                                                                    '—'}
                                                            </span>
                                                            <ArrowRight className="size-3.5 text-muted-foreground" />
                                                            <span className="font-medium">
                                                                {item.to_value ??
                                                                    '—'}
                                                            </span>
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            -
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="max-w-[16rem] truncate text-muted-foreground">
                                                    {item.reason ?? '-'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <ConfirmDialog
                                                        title="Hapus Peristiwa"
                                                        description={`Yakin hapus peristiwa ${label(types, item.type)} untuk ${item.employee_name}?`}
                                                        confirmLabel="Hapus"
                                                        onConfirm={() =>
                                                            handleDelete(
                                                                item.id,
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
