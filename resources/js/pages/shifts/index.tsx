import { Head, router, useForm } from '@inertiajs/react';
import { Clock, Moon, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import shifts from '@/actions/App/Http/Controllers/ShiftController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
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
import { useFlashToast } from '@/hooks/use-flash-toast';

type Shift = {
    id: number;
    code: string;
    name: string;
    start_time: string;
    end_time: string;
    break_min: number;
    is_overnight: boolean;
    late_tolerance_min: number;
    grace_min: number;
};

type IndexProps = { shifts: Shift[] };

type ShiftForm = {
    code: string;
    name: string;
    start_time: string;
    end_time: string;
    break_min: string;
    is_overnight: boolean;
    late_tolerance_min: string;
    grace_min: string;
};

const emptyForm: ShiftForm = {
    code: '',
    name: '',
    start_time: '08:00',
    end_time: '17:00',
    break_min: '60',
    is_overnight: false,
    late_tolerance_min: '15',
    grace_min: '5',
};

export default function ShiftsIndex({ shifts: rows }: IndexProps) {
    useFlashToast();

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Shift | null>(null);
    const form = useForm<ShiftForm>(emptyForm);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Shift) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            code: item.code,
            name: item.name,
            start_time: item.start_time,
            end_time: item.end_time,
            break_min: String(item.break_min),
            is_overnight: item.is_overnight,
            late_tolerance_min: String(item.late_tolerance_min),
            grace_min: String(item.grace_min),
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            break_min: Number(data.break_min),
            late_tolerance_min: Number(data.late_tolerance_min),
            grace_min: Number(data.grace_min),
        }));

        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(shifts.update.url(editing.id), opts);
        } else {
            form.post(shifts.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(shifts.destroy.url(id), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Jadwal & Shift" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Jadwal & Shift"
                    description="Kelola pola jam kerja dan toleransi keterlambatan."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Shift
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
                                        <TableHead>Jam Kerja</TableHead>
                                        <TableHead className="text-right">
                                            Istirahat
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Toleransi
                                        </TableHead>
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
                                                        <Clock className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada shift
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
                                                    <div className="flex items-center gap-2">
                                                        {item.name}
                                                        {item.is_overnight && (
                                                            <Badge
                                                                variant="secondary"
                                                                className="gap-1"
                                                            >
                                                                <Moon className="size-3" />
                                                                Lintas hari
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {item.start_time} –{' '}
                                                    {item.end_time}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.break_min} mnt
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.late_tolerance_min}{' '}
                                                    mnt
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
                                                            title="Hapus Shift"
                                                            description={`Yakin ingin menghapus "${item.name}"?`}
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
                            {editing ? 'Ubah Shift' : 'Tambah Shift'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="shift-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="sh-code">Kode</Label>
                                <Input
                                    id="sh-code"
                                    value={form.data.code}
                                    onChange={(event) =>
                                        form.setData('code', event.target.value)
                                    }
                                    placeholder="Mis. SH01"
                                    autoFocus
                                />
                                {form.errors.code && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.code}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="sh-name">Nama</Label>
                                <Input
                                    id="sh-name"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    placeholder="Mis. Shift Pagi"
                                />
                                {form.errors.name && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.name}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="sh-start">Jam Mulai</Label>
                                <Input
                                    id="sh-start"
                                    type="time"
                                    value={form.data.start_time}
                                    onChange={(event) =>
                                        form.setData(
                                            'start_time',
                                            event.target.value,
                                        )
                                    }
                                />
                                {form.errors.start_time && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.start_time}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="sh-end">Jam Selesai</Label>
                                <Input
                                    id="sh-end"
                                    type="time"
                                    value={form.data.end_time}
                                    onChange={(event) =>
                                        form.setData(
                                            'end_time',
                                            event.target.value,
                                        )
                                    }
                                />
                                {form.errors.end_time && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.end_time}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <NumberField
                                id="sh-break"
                                label="Istirahat (mnt)"
                                value={form.data.break_min}
                                onChange={(value) =>
                                    form.setData('break_min', value)
                                }
                                error={form.errors.break_min}
                            />
                            <NumberField
                                id="sh-late"
                                label="Toleransi (mnt)"
                                value={form.data.late_tolerance_min}
                                onChange={(value) =>
                                    form.setData('late_tolerance_min', value)
                                }
                                error={form.errors.late_tolerance_min}
                            />
                            <NumberField
                                id="sh-grace"
                                label="Grace (mnt)"
                                value={form.data.grace_min}
                                onChange={(value) =>
                                    form.setData('grace_min', value)
                                }
                                error={form.errors.grace_min}
                            />
                        </div>

                        <label className="flex items-center gap-2.5 text-sm">
                            <Checkbox
                                checked={form.data.is_overnight}
                                onCheckedChange={(value) =>
                                    form.setData('is_overnight', value === true)
                                }
                            />
                            Shift lintas hari (overnight)
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
                            form="shift-form"
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

function NumberField({
    id,
    label,
    value,
    onChange,
    error,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                type="number"
                min={0}
                max={1440}
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
