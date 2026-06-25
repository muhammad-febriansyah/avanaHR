import { Head, router, useForm } from '@inertiajs/react';
import { CalendarClock, Lock, LockOpen, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import payrollPeriods from '@/actions/App/Http/Controllers/PayrollPeriodController';
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
import { Input } from '@/components/ui/input';
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
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
    calculated: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
    reviewed: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-400',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    locked: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    disbursed: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
};

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : '-';
}

type PeriodForm = {
    code: string;
    month: string;
    year: string;
    cutoff_date: string;
    pay_date: string;
    status: string;
};

function emptyForm(): PeriodForm {
    return {
        code: '',
        month: '1',
        year: '2026',
        cutoff_date: '',
        pay_date: '',
        status: 'draft',
    };
}

export default function PayrollPeriodsIndex({
    periods: rows,
    statuses,
}: IndexProps) {
    useFlashToast();

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Period | null>(null);
    const form = useForm<PeriodForm>(emptyForm());

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm());
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Period) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            code: item.code,
            month: String(item.month),
            year: String(item.year),
            cutoff_date: item.cutoff_date ?? '',
            pay_date: item.pay_date ?? '',
            status: item.status,
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            month: Number(data.month),
            year: Number(data.year),
            cutoff_date: data.cutoff_date || null,
            pay_date: data.pay_date || null,
        }));

        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(payrollPeriods.update.url(editing.id), opts);
        } else {
            form.post(payrollPeriods.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(payrollPeriods.destroy.url(id), { preserveScroll: true });
    }

    function handleClose(id: number) {
        router.post(payrollPeriods.close.url(id), {}, { preserveScroll: true });
    }

    function handleReopen(id: number) {
        router.post(payrollPeriods.reopen.url(id), {}, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Periode Payroll" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Periode Payroll"
                    description="Kelola periode penggajian bulanan."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Periode
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
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
                                                colSpan={6}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <CalendarClock className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada periode payroll
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item) => (
                                            <TableRow key={item.id}>
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
                                                        {item.status}
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
                                                                        variant="outline"
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
                                                                        variant="outline"
                                                                    >
                                                                        <LockOpen />
                                                                        Buka
                                                                    </Button>
                                                                }
                                                            />
                                                        )}
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

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editing
                                ? 'Ubah Periode Payroll'
                                : 'Tambah Periode Payroll'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="period-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="pp-code">Kode</Label>
                            <Input
                                id="pp-code"
                                value={form.data.code}
                                onChange={(event) =>
                                    form.setData('code', event.target.value)
                                }
                                placeholder="Mis. PR-2026-07"
                                autoFocus
                            />
                            {form.errors.code && (
                                <p className="text-sm text-destructive">
                                    {form.errors.code}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="pp-month">Bulan</Label>
                                <Select
                                    value={form.data.month}
                                    onValueChange={(value) =>
                                        form.setData('month', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="pp-month"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {MONTHS.map((label, index) => (
                                            <SelectItem
                                                key={label}
                                                value={String(index + 1)}
                                            >
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="pp-year">Tahun</Label>
                                <Input
                                    id="pp-year"
                                    type="number"
                                    min={2000}
                                    max={2100}
                                    value={form.data.year}
                                    onChange={(event) =>
                                        form.setData('year', event.target.value)
                                    }
                                />
                                {form.errors.year && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.year}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="pp-cutoff">
                                    Tanggal Cut-off
                                </Label>
                                <Input
                                    id="pp-cutoff"
                                    type="date"
                                    value={form.data.cutoff_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'cutoff_date',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="pp-pay">Tanggal Bayar</Label>
                                <Input
                                    id="pp-pay"
                                    type="date"
                                    value={form.data.pay_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'pay_date',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        {editing && (
                            <div className="grid gap-2">
                                <Label htmlFor="pp-status">Status</Label>
                                <Select
                                    value={form.data.status}
                                    onValueChange={(value) =>
                                        form.setData('status', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="pp-status"
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
                            form="period-form"
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
