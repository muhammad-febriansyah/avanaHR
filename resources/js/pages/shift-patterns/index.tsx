import { Head, router, useForm } from '@inertiajs/react';
import { CalendarRange, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import shiftPatterns from '@/actions/App/Http/Controllers/ShiftPatternController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
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

type ShiftOption = { id: number; label: string };

type Pattern = {
    id: number;
    name: string;
    type: string;
    days: Array<number | null>;
};

type IndexProps = {
    patterns: Pattern[];
    shifts: ShiftOption[];
};

const OFF = 'off';

export default function ShiftPatternsIndex({ patterns, shifts }: IndexProps) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Pattern | null>(null);

    const form = useForm<{ name: string; type: string; days: string[] }>({
        name: '',
        type: 'cyclic',
        days: [OFF],
    });

    const shiftLabel = (id: number | null) =>
        id === null
            ? 'Libur'
            : (shifts.find((s) => s.id === id)?.label ?? `Shift #${id}`);

    function openCreate() {
        setEditing(null);
        form.clearErrors();
        form.setData({ name: '', type: 'cyclic', days: [OFF] });
        setOpen(true);
    }

    function openEdit(pattern: Pattern) {
        setEditing(pattern);
        form.clearErrors();
        form.setData({
            name: pattern.name,
            type: pattern.type,
            days: pattern.days.map((d) => (d === null ? OFF : String(d))),
        });
        setOpen(true);
    }

    function setDay(index: number, value: string) {
        form.setData(
            'days',
            form.data.days.map((d, i) => (i === index ? value : d)),
        );
    }

    function addDay() {
        form.setData('days', [...form.data.days, OFF]);
    }

    function removeDay(index: number) {
        form.setData(
            'days',
            form.data.days.filter((_, i) => i !== index),
        );
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            days: data.days.map((d) => (d === OFF ? null : Number(d))),
        }));

        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            form.put(shiftPatterns.update.url(editing.id), options);
        } else {
            form.post(shiftPatterns.store.url(), options);
        }
    }

    return (
        <>
            <Head title="Pola Shift" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Pola Shift"
                    description="Pola rotasi shift yang dipakai untuk membuat roster otomatis."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Pola
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Siklus</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {patterns.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={3}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            <CalendarRange className="mx-auto mb-2 size-6 opacity-50" />
                                            Belum ada pola shift
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    patterns.map((pattern) => (
                                        <TableRow key={pattern.id}>
                                            <TableCell className="font-medium text-navy">
                                                {pattern.name}
                                            </TableCell>
                                            <TableCell className="flex flex-wrap gap-1">
                                                {pattern.days.map((d, i) => (
                                                    <span
                                                        key={i}
                                                        className="rounded bg-muted px-1.5 py-0.5 text-xs"
                                                    >
                                                        {shiftLabel(d)}
                                                    </span>
                                                ))}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        openEdit(pattern)
                                                    }
                                                >
                                                    <Pencil />
                                                </Button>
                                                <ConfirmDialog
                                                    title="Hapus pola shift?"
                                                    description={`Pola "${pattern.name}" akan dihapus.`}
                                                    confirmLabel="Hapus"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            shiftPatterns.destroy.url(
                                                                pattern.id,
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
                        <DialogTitle>
                            {editing ? 'Edit Pola Shift' : 'Tambah Pola Shift'}
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                Nama Pola <RequiredMark />
                            </Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder="Mis. Rotasi 2-2-Off"
                            />
                            {form.errors.name && (
                                <p className="text-sm text-destructive">
                                    {form.errors.name}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label>
                                Siklus Hari <RequiredMark />
                            </Label>
                            <p className="text-xs text-muted-foreground">
                                Urutan shift per hari, diulang sepanjang rentang
                                tanggal. Pilih “Libur” untuk hari kosong.
                            </p>
                            <div className="grid gap-2">
                                {form.data.days.map((day, index) => (
                                    <div
                                        key={index}
                                        className="flex items-center gap-2"
                                    >
                                        <span className="w-14 text-sm text-muted-foreground">
                                            Hari {index + 1}
                                        </span>
                                        <Select
                                            value={day}
                                            onValueChange={(value) =>
                                                setDay(index, value)
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Pilih shift" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={OFF}>
                                                    Libur
                                                </SelectItem>
                                                {shifts.map((shift) => (
                                                    <SelectItem
                                                        key={shift.id}
                                                        value={String(shift.id)}
                                                    >
                                                        {shift.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            disabled={
                                                form.data.days.length <= 1
                                            }
                                            onClick={() => removeDay(index)}
                                        >
                                            <X />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addDay}
                            >
                                <Plus />
                                Tambah Hari
                            </Button>
                            {form.errors.days && (
                                <p className="text-sm text-destructive">
                                    {form.errors.days}
                                </p>
                            )}
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
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
