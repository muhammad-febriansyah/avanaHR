import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Cog,
    Eye,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import payrollRuns from '@/actions/App/Http/Controllers/PayrollRunController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
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
type PeriodOption = { id: number; code: string };

type Run = {
    id: number;
    run_no: string;
    period_code: string | null;
    type: string;
    status: string;
    gross_total: number;
    net_total: number;
    payslips_count: number;
};

type Filters = { period_id?: string; status?: string };

type IndexProps = {
    runs: Paginator<Run>;
    filters: Filters;
    types: Option[];
    statuses: Option[];
    options: { periods: PeriodOption[] };
};

const ALL_PERIOD = 'all';
const ALL_STATUS = 'all';

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
    calculated: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    paid: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
};

const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
});

function labelOf(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

type RunForm = { period_id: string; type: string; status: string };

const emptyForm: RunForm = { period_id: '', type: 'regular', status: 'draft' };

export default function PayrollRunsIndex({
    runs: paginator,
    filters = {},
    types,
    statuses,
    options,
}: IndexProps) {
    useFlashToast();

    const [periodId, setPeriodId] = useState(filters.period_id ?? ALL_PERIOD);
    const [status, setStatus] = useState(filters.status ?? ALL_STATUS);

    function go(next: { period_id?: string; status?: string }) {
        const p = next.period_id ?? periodId;
        const s = next.status ?? status;
        router.get(
            payrollRuns.index.url(),
            {
                period_id: p === ALL_PERIOD ? undefined : p,
                status: s === ALL_STATUS ? undefined : s,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Run | null>(null);
    const form = useForm<RunForm>(emptyForm);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Run) {
        setEditing(item);
        form.clearErrors();
        form.setData({ period_id: '', type: item.type, status: item.status });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(payrollRuns.update.url(editing.id), opts);
        } else {
            form.post(payrollRuns.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(payrollRuns.destroy.url(id), { preserveScroll: true });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Proses Payroll" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Proses Payroll"
                    description="Kelola batch proses perhitungan gaji per periode."
                >
                    <Button
                        onClick={openCreate}
                        disabled={options.periods.length === 0}
                    >
                        <Plus />
                        Buat Proses
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Select
                                value={periodId}
                                onValueChange={(value) => {
                                    setPeriodId(value);
                                    go({ period_id: value });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-52">
                                    <SelectValue placeholder="Semua Periode" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_PERIOD}>
                                        Semua Periode
                                    </SelectItem>
                                    {options.periods.map((period) => (
                                        <SelectItem
                                            key={period.id}
                                            value={String(period.id)}
                                        >
                                            {period.code}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={status}
                                onValueChange={(value) => {
                                    setStatus(value);
                                    go({ status: value });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
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
                                        <TableHead>No. Run</TableHead>
                                        <TableHead>Periode</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead className="text-right">
                                            Slip
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Gross
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Net
                                        </TableHead>
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
                                                colSpan={8}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <Cog className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada proses payroll
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="font-medium">
                                                    {item.run_no}
                                                </TableCell>
                                                <TableCell>
                                                    {item.period_code}
                                                </TableCell>
                                                <TableCell>
                                                    {labelOf(types, item.type)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.payslips_count}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {rupiah.format(
                                                        item.gross_total,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {rupiah.format(
                                                        item.net_total,
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
                                                        {labelOf(
                                                            statuses,
                                                            item.status,
                                                        )}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="outline"
                                                        >
                                                            <Link
                                                                href={payrollRuns.show.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Eye />
                                                                Lihat
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                openEdit(item)
                                                            }
                                                        >
                                                            <Pencil />
                                                            Edit
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Proses Payroll"
                                                            description={`Yakin ingin menghapus proses "${item.run_no}"?`}
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

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} proses
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
                                    variant="outline"
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

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editing
                                ? 'Ubah Proses Payroll'
                                : 'Buat Proses Payroll'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="run-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        {editing ? (
                            <div className="rounded-lg border bg-muted/40 p-3 text-sm">
                                <div className="font-medium">
                                    {editing.run_no}
                                </div>
                                <div className="text-muted-foreground">
                                    {editing.period_code}
                                </div>
                            </div>
                        ) : (
                            <div className="grid gap-2">
                                <Label htmlFor="pr-period">Periode</Label>
                                <Select
                                    value={form.data.period_id}
                                    onValueChange={(value) =>
                                        form.setData('period_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="pr-period"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih periode" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.periods.map((period) => (
                                            <SelectItem
                                                key={period.id}
                                                value={String(period.id)}
                                            >
                                                {period.code}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.period_id && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.period_id}
                                    </p>
                                )}
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="pr-type">Tipe Run</Label>
                            <Select
                                value={form.data.type}
                                onValueChange={(value) =>
                                    form.setData('type', value)
                                }
                            >
                                <SelectTrigger id="pr-type" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
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

                        {editing && (
                            <div className="grid gap-2">
                                <Label htmlFor="pr-status">Status</Label>
                                <Select
                                    value={form.data.status}
                                    onValueChange={(value) =>
                                        form.setData('status', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="pr-status"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
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
                        )}
                    </form>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setOpen(false)}
                        >
                            <X />
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="run-form"
                            disabled={form.processing}
                        >
                            {editing ? 'Simpan Perubahan' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
