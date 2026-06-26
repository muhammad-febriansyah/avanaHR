import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeftRight, Repeat, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import shiftSwaps from '@/actions/App/Http/Controllers/ShiftSwapController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
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

type Option = { id: number; label: string };

type Swap = {
    id: number;
    requester: string | null;
    target: string | null;
    date_a: string | null;
    date_b: string | null;
    status: string;
};

type Paginated<T> = { data: T[] };

type IndexProps = {
    swaps: Paginated<Swap>;
    employees: Option[];
};

const STATUS: Record<string, { label: string; className: string }> = {
    pending: { label: 'Pending', className: 'bg-amber-100 text-amber-700' },
    approved: {
        label: 'Disetujui',
        className: 'bg-emerald-100 text-emerald-700',
    },
    rejected: { label: 'Ditolak', className: 'bg-red-100 text-red-700' },
};

export default function ShiftSwapsIndex({ swaps, employees }: IndexProps) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        requester_id: '',
        target_id: '',
        date_a: '',
        date_b: '',
    });

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        form.post(shiftSwaps.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    }

    return (
        <>
            <Head title="Tukar Shift" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tukar Shift"
                    description="Pengajuan tukar jadwal shift antar karyawan, lewat persetujuan."
                >
                    <Button onClick={() => setOpen(true)}>
                        <ArrowLeftRight />
                        Ajukan Tukar
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Pengaju</TableHead>
                                    <TableHead>Tujuan</TableHead>
                                    <TableHead>Tanggal Pengaju</TableHead>
                                    <TableHead>Tanggal Tujuan</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {swaps.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            <Repeat className="mx-auto mb-2 size-6 opacity-50" />
                                            Belum ada pengajuan tukar shift
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    swaps.data.map((swap) => (
                                        <TableRow key={swap.id}>
                                            <TableCell className="font-medium text-navy">
                                                {swap.requester}
                                            </TableCell>
                                            <TableCell>{swap.target}</TableCell>
                                            <TableCell>{swap.date_a}</TableCell>
                                            <TableCell>{swap.date_b}</TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="secondary"
                                                    className={
                                                        STATUS[swap.status]
                                                            ?.className
                                                    }
                                                >
                                                    {STATUS[swap.status]
                                                        ?.label ?? swap.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ConfirmDialog
                                                    title="Hapus pengajuan?"
                                                    description="Pengajuan tukar shift akan dihapus."
                                                    confirmLabel="Hapus"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            shiftSwaps.destroy.url(
                                                                swap.id,
                                                            ),
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <Trash2 className="text-destructive" />
                                                        </Button>
                                                    }
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ajukan Tukar Shift</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label>
                                Karyawan Pengaju <RequiredMark />
                            </Label>
                            <Select
                                value={form.data.requester_id}
                                onValueChange={(v) =>
                                    form.setData('requester_id', v)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih karyawan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {employees.map((e) => (
                                        <SelectItem
                                            key={e.id}
                                            value={String(e.id)}
                                        >
                                            {e.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.errors.requester_id && (
                                <p className="text-sm text-destructive">
                                    {form.errors.requester_id}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label>
                                Karyawan Tujuan <RequiredMark />
                            </Label>
                            <Select
                                value={form.data.target_id}
                                onValueChange={(v) =>
                                    form.setData('target_id', v)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih karyawan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {employees.map((e) => (
                                        <SelectItem
                                            key={e.id}
                                            value={String(e.id)}
                                        >
                                            {e.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.errors.target_id && (
                                <p className="text-sm text-destructive">
                                    {form.errors.target_id}
                                </p>
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>
                                    Tanggal Pengaju <RequiredMark />
                                </Label>
                                <DatePicker
                                    value={form.data.date_a}
                                    onChange={(v) => form.setData('date_a', v)}
                                    placeholder="Tanggal"
                                />
                                {form.errors.date_a && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.date_a}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label>
                                    Tanggal Tujuan <RequiredMark />
                                </Label>
                                <DatePicker
                                    value={form.data.date_b}
                                    onChange={(v) => form.setData('date_b', v)}
                                    placeholder="Tanggal"
                                />
                                {form.errors.date_b && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.date_b}
                                    </p>
                                )}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                Ajukan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
