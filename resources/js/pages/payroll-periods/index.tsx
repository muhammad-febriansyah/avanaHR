import { Head, Link, router } from '@inertiajs/react';
import {
    CalendarClock,
    Lock,
    LockOpen,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import payrollPeriods from '@/actions/App/Http/Controllers/PayrollPeriodController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFlashToast } from '@/hooks/use-flash-toast';

type StatusOption = { value: string; label: string };

type Period = {
    id: number;
    code: string;
    month: number;
    year: number;
    cutoff_date: string | null;
    pay_date: string | null;
    status: string;
    can_close: boolean;
    can_reopen: boolean;
    runs_count: number;
};

type IndexProps = { periods: Period[]; statuses: StatusOption[] };

const MONTHS = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
    calculated:
        'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
    reviewed:
        'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-400',
    approved:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    locked: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    disbursed:
        'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
};

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : '-';
}

function labelOf(options: StatusOption[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

export default function PayrollPeriodsIndex({
    periods: rows,
    statuses,
}: IndexProps) {
    useFlashToast();

    function handleDelete(id: number) {
        router.delete(payrollPeriods.destroy.url(id), { preserveScroll: true });
    }

    function handleClose(id: number) {
        router.post(payrollPeriods.close.url(id), {}, { preserveScroll: true });
    }

    function handleReopen(id: number) {
        router.post(
            payrollPeriods.reopen.url(id),
            {},
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Periode Payroll" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Periode Payroll"
                    description="Kelola periode penggajian bulanan."
                >
                    <Button asChild>
                        <Link href={payrollPeriods.create.url()}>
                            <Plus />
                            Tambah Periode
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Periode</TableHead>
                                        <TableHead>Cut-off</TableHead>
                                        <TableHead>Tgl Bayar</TableHead>
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
                                                        <CalendarClock className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada periode
                                                        payroll
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item, index) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="text-muted-foreground tabular-nums">
                                                    {index + 1}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {item.code}
                                                </TableCell>
                                                <TableCell>
                                                    {MONTHS[item.month - 1]}{' '}
                                                    {item.year}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDate(
                                                        item.cutoff_date,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDate(item.pay_date)}
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
                                                        {labelOf(
                                                            statuses,
                                                            item.status,
                                                        )}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        {item.can_close && (
                                                            <ConfirmDialog
                                                                title="Kunci Periode Payroll"
                                                                description="Kunci periode ini? Tidak bisa membuat proses payroll baru di periode ini."
                                                                confirmLabel="Kunci"
                                                                onConfirm={() =>
                                                                    handleClose(
                                                                        item.id,
                                                                    )
                                                                }
                                                                trigger={
                                                                    <Button
                                                                        size="sm"
                                                                        variant="secondary"
                                                                    >
                                                                        <Lock />
                                                                        Kunci
                                                                    </Button>
                                                                }
                                                            />
                                                        )}
                                                        {item.can_reopen && (
                                                            <ConfirmDialog
                                                                title="Buka Periode Payroll"
                                                                description="Buka kembali periode ini? Proses payroll baru dapat dibuat lagi."
                                                                confirmLabel="Buka"
                                                                onConfirm={() =>
                                                                    handleReopen(
                                                                        item.id,
                                                                    )
                                                                }
                                                                trigger={
                                                                    <Button
                                                                        size="sm"
                                                                        variant="secondary"
                                                                    >
                                                                        <LockOpen />
                                                                        Buka
                                                                    </Button>
                                                                }
                                                            />
                                                        )}
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="success"
                                                        >
                                                            <Link
                                                                href={payrollPeriods.edit.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Periode Payroll"
                                                            description={`Yakin ingin menghapus periode "${item.code}"?`}
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
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
