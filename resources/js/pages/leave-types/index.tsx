import { Head, router, useForm } from '@inertiajs/react';
import { CalendarOff, Pencil, Plus, Trash2, X } from 'lucide-react';
import {  useState } from 'react';
import type {FormEvent} from 'react';
import leaveTypes from '@/actions/App/Http/Controllers/LeaveTypeController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type LeaveType = {
    id: number;
    code: string;
    name: string;
    is_paid: boolean;
    max_days_year: number | null;
    allow_negative: boolean;
};

type IndexProps = {
    leaveTypes: LeaveType[];
};

type FormData = {
    code: string;
    name: string;
    is_paid: boolean;
    max_days_year: string;
    allow_negative: boolean;
};

const emptyForm: FormData = {
    code: '',
    name: '',
    is_paid: true,
    max_days_year: '',
    allow_negative: false,
};

export default function LeaveTypesIndex({ leaveTypes: rows }: IndexProps) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<LeaveType | null>(null);

    const form = useForm<FormData>(emptyForm);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: LeaveType) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            code: item.code,
            name: item.name,
            is_paid: item.is_paid,
            max_days_year: item.max_days_year?.toString() ?? '',
            allow_negative: item.allow_negative,
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        const payload = {
            ...form.data,
            max_days_year:
                form.data.max_days_year === ''
                    ? null
                    : Number(form.data.max_days_year),
        };

        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        form.transform(() => payload);

        if (editing) {
            form.put(leaveTypes.update.url(editing.id), options);
        } else {
            form.post(leaveTypes.store.url(), options);
        }
    }

    function handleDelete(id: number) {
        router.delete(leaveTypes.destroy.url(id), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Jenis Cuti" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Jenis Cuti"
                    description="Kelola kategori cuti yang berlaku di perusahaan."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Jenis Cuti
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Dibayar</TableHead>
                                        <TableHead>Maks. Hari/Tahun</TableHead>
                                        <TableHead>Saldo Minus</TableHead>
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
                                                        <CalendarOff className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada jenis cuti
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
                                                    {item.name}
                                                </TableCell>
                                                <TableCell>
                                                    {item.is_paid
                                                        ? 'Ya'
                                                        : 'Tidak'}
                                                </TableCell>
                                                <TableCell>
                                                    {item.max_days_year ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {item.allow_negative
                                                        ? 'Diizinkan'
                                                        : 'Tidak'}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
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
                                                            title="Hapus Jenis Cuti"
                                                            description={`Yakin ingin menghapus "${item.name}"? Tindakan ini tidak dapat dibatalkan.`}
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
                            {editing ? 'Ubah Jenis Cuti' : 'Tambah Jenis Cuti'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="leave-type-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="code">Kode</Label>
                            <Input
                                id="code"
                                value={form.data.code}
                                onChange={(event) =>
                                    form.setData('code', event.target.value)
                                }
                                placeholder="Mis. CT, CS"
                                autoFocus
                            />
                            {form.errors.code && (
                                <p className="text-sm text-destructive">
                                    {form.errors.code}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                placeholder="Mis. Cuti Tahunan"
                            />
                            {form.errors.name && (
                                <p className="text-sm text-destructive">
                                    {form.errors.name}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="max_days_year">
                                Maksimal Hari per Tahun
                            </Label>
                            <Input
                                id="max_days_year"
                                type="number"
                                min={0}
                                max={365}
                                value={form.data.max_days_year}
                                onChange={(event) =>
                                    form.setData(
                                        'max_days_year',
                                        event.target.value,
                                    )
                                }
                                placeholder="Kosongkan jika tidak ada batas"
                            />
                            {form.errors.max_days_year && (
                                <p className="text-sm text-destructive">
                                    {form.errors.max_days_year}
                                </p>
                            )}
                        </div>

                        <label className="flex items-center gap-2.5 text-sm">
                            <Checkbox
                                checked={form.data.is_paid}
                                onCheckedChange={(value) =>
                                    form.setData('is_paid', value === true)
                                }
                            />
                            Cuti dibayar (berbayar)
                        </label>

                        <label className="flex items-center gap-2.5 text-sm">
                            <Checkbox
                                checked={form.data.allow_negative}
                                onCheckedChange={(value) =>
                                    form.setData(
                                        'allow_negative',
                                        value === true,
                                    )
                                }
                            />
                            Izinkan saldo minus
                        </label>
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
                            form="leave-type-form"
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
