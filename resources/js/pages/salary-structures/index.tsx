import { Head, router, useForm } from '@inertiajs/react';
import { Layers, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import salaryStructures from '@/actions/App/Http/Controllers/SalaryStructureController';
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
import { Label } from '@/components/ui/label';
import { RupiahInput } from '@/components/ui/rupiah-input';
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
import { formatRupiah } from '@/lib/format';

type Structure = {
    id: number;
    job_grade_id: number;
    grade_code: string | null;
    grade_name: string | null;
    band_min: number;
    band_max: number;
    currency: string | null;
};

type GradeOption = { id: number; label: string };

type IndexProps = {
    structures: Structure[];
    grades: GradeOption[];
};

export default function SalaryStructuresIndex({
    structures,
    grades,
}: IndexProps) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Structure | null>(null);

    const form = useForm<{
        job_grade_id: string;
        band_min: string;
        band_max: string;
    }>({ job_grade_id: '', band_min: '', band_max: '' });

    function openCreate() {
        setEditing(null);
        form.clearErrors();
        form.setData({ job_grade_id: '', band_min: '', band_max: '' });
        setOpen(true);
    }

    function openEdit(structure: Structure) {
        setEditing(structure);
        form.clearErrors();
        form.setData({
            job_grade_id: String(structure.job_grade_id),
            band_min: String(structure.band_min),
            band_max: String(structure.band_max),
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            form.put(salaryStructures.update.url(editing.id), options);
        } else {
            form.post(salaryStructures.store.url(), options);
        }
    }

    return (
        <>
            <Head title="Struktur Gaji" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Struktur Gaji"
                    description="Rentang gaji (band) per grade jabatan sebagai acuan kompensasi."
                >
                    <Button onClick={openCreate} disabled={grades.length === 0}>
                        <Plus />
                        Tambah Struktur
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Grade</TableHead>
                                    <TableHead className="text-right">
                                        Batas Bawah
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Batas Atas
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {structures.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            <Layers className="mx-auto mb-2 size-6 opacity-50" />
                                            Belum ada struktur gaji
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    structures.map((structure) => (
                                        <TableRow key={structure.id}>
                                            <TableCell className="font-medium text-navy">
                                                {structure.grade_code}
                                                <span className="block text-xs text-muted-foreground">
                                                    {structure.grade_name}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatRupiah(
                                                    structure.band_min,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatRupiah(
                                                    structure.band_max,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        openEdit(structure)
                                                    }
                                                >
                                                    <Pencil />
                                                </Button>
                                                <ConfirmDialog
                                                    title="Hapus struktur gaji?"
                                                    description={`Struktur grade ${structure.grade_code} akan dihapus.`}
                                                    confirmLabel="Hapus"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            salaryStructures.destroy.url(
                                                                structure.id,
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
                            {editing
                                ? `Edit Struktur — ${editing.grade_code}`
                                : 'Tambah Struktur Gaji'}
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="grid gap-4">
                        {!editing && (
                            <div className="grid gap-2">
                                <Label>
                                    Grade Jabatan <RequiredMark />
                                </Label>
                                <Select
                                    value={form.data.job_grade_id}
                                    onValueChange={(v) =>
                                        form.setData('job_grade_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih grade" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {grades.map((g) => (
                                            <SelectItem
                                                key={g.id}
                                                value={String(g.id)}
                                            >
                                                {g.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.job_grade_id && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.job_grade_id}
                                    </p>
                                )}
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="band_min">
                                    Batas Bawah (Rp) <RequiredMark />
                                </Label>
                                <RupiahInput
                                    id="band_min"
                                    value={form.data.band_min}
                                    onChange={(v) =>
                                        form.setData('band_min', v)
                                    }
                                    placeholder="Mis. 8000000"
                                />
                                {form.errors.band_min && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.band_min}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="band_max">
                                    Batas Atas (Rp) <RequiredMark />
                                </Label>
                                <RupiahInput
                                    id="band_max"
                                    value={form.data.band_max}
                                    onChange={(v) =>
                                        form.setData('band_max', v)
                                    }
                                    placeholder="Mis. 15000000"
                                />
                                {form.errors.band_max && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.band_max}
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
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
