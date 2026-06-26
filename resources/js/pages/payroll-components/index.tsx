import { Head, router, useForm } from '@inertiajs/react';
import { Coins, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import payrollComponents from '@/actions/App/Http/Controllers/PayrollComponentController';
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

type Component = {
    id: number;
    code: string;
    name: string;
    type: string;
    calc_type: string;
    is_taxable: boolean;
    is_bpjs_base: boolean;
};

type IndexProps = { components: Component[] };

const TYPE_LABELS: Record<string, string> = {
    earning: 'Pendapatan',
    deduction: 'Potongan',
};

const CALC_LABELS: Record<string, string> = {
    fixed: 'Nominal Tetap',
    percentage: 'Persentase',
    formula: 'Formula',
};

type ComponentForm = {
    code: string;
    name: string;
    type: string;
    calc_type: string;
    is_taxable: boolean;
    is_bpjs_base: boolean;
};

const emptyForm: ComponentForm = {
    code: '',
    name: '',
    type: 'earning',
    calc_type: 'fixed',
    is_taxable: true,
    is_bpjs_base: false,
};

export default function PayrollComponentsIndex({
    components: rows,
}: IndexProps) {
    useFlashToast();

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Component | null>(null);
    const form = useForm<ComponentForm>(emptyForm);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Component) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            code: item.code,
            name: item.name,
            type: item.type,
            calc_type: item.calc_type,
            is_taxable: item.is_taxable,
            is_bpjs_base: item.is_bpjs_base,
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(payrollComponents.update.url(editing.id), opts);
        } else {
            form.post(payrollComponents.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(payrollComponents.destroy.url(id), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Komponen Gaji" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Komponen Gaji"
                    description="Kelola komponen pendapatan dan potongan untuk payroll."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Komponen
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
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Metode</TableHead>
                                        <TableHead>Pajak</TableHead>
                                        <TableHead>Dasar BPJS</TableHead>
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
                                                        <Coins className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada komponen gaji
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
                                                    {item.name}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={
                                                            item.type ===
                                                            'earning'
                                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                                : 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400'
                                                        }
                                                    >
                                                        {TYPE_LABELS[
                                                            item.type
                                                        ] ?? item.type}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {CALC_LABELS[
                                                        item.calc_type
                                                    ] ?? item.calc_type}
                                                </TableCell>
                                                <TableCell>
                                                    {item.is_taxable
                                                        ? 'Ya'
                                                        : 'Tidak'}
                                                </TableCell>
                                                <TableCell>
                                                    {item.is_bpjs_base
                                                        ? 'Ya'
                                                        : 'Tidak'}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="success"
                                                            onClick={() =>
                                                                openEdit(item)
                                                            }
                                                        >
                                                            <Pencil />
                                                            Edit
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Komponen Gaji"
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
                            {editing
                                ? 'Ubah Komponen Gaji'
                                : 'Tambah Komponen Gaji'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="component-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="pc-code">Kode</Label>
                                <Input
                                    id="pc-code"
                                    value={form.data.code}
                                    onChange={(event) =>
                                        form.setData('code', event.target.value)
                                    }
                                    placeholder="Mis. BASIC"
                                    autoFocus
                                />
                                {form.errors.code && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.code}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="pc-name">Nama</Label>
                                <Input
                                    id="pc-name"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    placeholder="Mis. Gaji Pokok"
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
                                <Label htmlFor="pc-type">Tipe</Label>
                                <Select
                                    value={form.data.type}
                                    onValueChange={(value) =>
                                        form.setData('type', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="pc-type"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="earning">
                                            Pendapatan
                                        </SelectItem>
                                        <SelectItem value="deduction">
                                            Potongan
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="pc-calc">Metode Hitung</Label>
                                <Select
                                    value={form.data.calc_type}
                                    onValueChange={(value) =>
                                        form.setData('calc_type', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="pc-calc"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="fixed">
                                            Nominal Tetap
                                        </SelectItem>
                                        <SelectItem value="percentage">
                                            Persentase
                                        </SelectItem>
                                        <SelectItem value="formula">
                                            Formula
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <label className="flex items-center gap-2.5 text-sm">
                            <Checkbox
                                checked={form.data.is_taxable}
                                onCheckedChange={(value) =>
                                    form.setData('is_taxable', value === true)
                                }
                            />
                            Kena pajak (PPh21)
                        </label>

                        <label className="flex items-center gap-2.5 text-sm">
                            <Checkbox
                                checked={form.data.is_bpjs_base}
                                onCheckedChange={(value) =>
                                    form.setData('is_bpjs_base', value === true)
                                }
                            />
                            Masuk dasar perhitungan BPJS
                        </label>
                    </form>

                    <DialogFooter>
                        <Button
                            variant="secondary"
                            type="button"
                            onClick={() => setOpen(false)}
                        >
                            <X />
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="component-form"
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
