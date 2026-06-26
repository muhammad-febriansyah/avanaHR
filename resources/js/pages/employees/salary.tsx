import { Head, router, useForm } from '@inertiajs/react';
import { Coins, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import salary from '@/actions/App/Http/Controllers/EmployeeSalaryComponentController';
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
import { formatRupiah } from '@/lib/format';

type ComponentType = 'earning' | 'deduction';

type Component = {
    id: number;
    code: string;
    name: string;
    type: ComponentType | null;
    calc_type?: string | null;
};

type SalaryComponent = {
    id: number;
    component_id: number;
    component_code: string | null;
    component_name: string | null;
    type: ComponentType | null;
    calc_type?: string | null;
    amount: number;
    rate?: number;
    effective_date: string | null;
};

type SalaryProps = {
    employee: { id: number; name: string; employee_no: string };
    salaryComponents: SalaryComponent[];
    components: Component[];
};

type FormData = {
    component_id: string;
    amount: string;
    rate: string;
    effective_date: string;
};

const emptyForm: FormData = {
    component_id: '',
    amount: '',
    rate: '',
    effective_date: '',
};

const TYPE_LABELS: Record<ComponentType, string> = {
    earning: 'Penghasilan',
    deduction: 'Potongan',
};

function TypeBadge({ type }: { type: ComponentType | null }) {
    if (type === 'earning') {
        return (
            <Badge
                variant="secondary"
                className="bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400"
            >
                Penghasilan
            </Badge>
        );
    }

    if (type === 'deduction') {
        return (
            <Badge
                variant="secondary"
                className="bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400"
            >
                Potongan
            </Badge>
        );
    }

    return <span className="text-muted-foreground">-</span>;
}

export default function EmployeeSalary({
    employee,
    salaryComponents,
    components,
}: SalaryProps) {
    useFlashToast();

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<SalaryComponent | null>(null);

    const form = useForm<FormData>(emptyForm);

    const selectedComponent = components.find(
        (c) => c.id.toString() === form.data.component_id,
    );
    const isPercentage = selectedComponent?.calc_type === 'percentage';

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: SalaryComponent) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            component_id: item.component_id.toString(),
            amount: item.amount.toString(),
            rate: item.rate ? item.rate.toString() : '',
            effective_date: item.effective_date ?? '',
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        const payload = {
            component_id: form.data.component_id
                ? Number(form.data.component_id)
                : null,
            amount: form.data.amount === '' ? 0 : Number(form.data.amount),
            rate: form.data.rate === '' ? null : Number(form.data.rate),
            effective_date: form.data.effective_date,
        };

        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        form.transform(() => payload);

        if (editing) {
            form.patch(salary.update.url(editing.id), options);
        } else {
            form.post(salary.store.url(employee.id), options);
        }
    }

    function handleDelete(id: number) {
        router.delete(salary.destroy.url(id), { preserveScroll: true });
    }

    return (
        <>
            <Head title={`Komponen Gaji — ${employee.name}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={`Komponen Gaji — ${employee.name}`}
                    description="Gaji pokok, tunjangan, dan potongan karyawan."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Komponen
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Komponen</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead className="text-right">
                                            Nominal
                                        </TableHead>
                                        <TableHead>Berlaku</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {salaryComponents.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
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
                                        salaryComponents.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="font-medium">
                                                    {item.component_name ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <TypeBadge
                                                        type={item.type}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.calc_type ===
                                                    'percentage'
                                                        ? `${item.rate ?? 0}%`
                                                        : formatRupiah(
                                                              item.amount,
                                                          )}
                                                </TableCell>
                                                <TableCell>
                                                    {item.effective_date ?? '-'}
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
                                                            title="Hapus Komponen Gaji"
                                                            description={`Yakin ingin menghapus "${item.component_name ?? 'komponen'}"? Tindakan ini tidak dapat dibatalkan.`}
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

                        <p className="text-sm text-muted-foreground">
                            {salaryComponents.length} komponen gaji
                        </p>
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
                        id="salary-component-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="component_id">Komponen</Label>
                            <Select
                                value={form.data.component_id}
                                onValueChange={(value) =>
                                    form.setData('component_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="component_id"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Pilih komponen" />
                                </SelectTrigger>
                                <SelectContent>
                                    {components.map((component) => (
                                        <SelectItem
                                            key={component.id}
                                            value={component.id.toString()}
                                        >
                                            {component.name}
                                            {component.type
                                                ? ` · ${TYPE_LABELS[component.type]}`
                                                : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.errors.component_id && (
                                <p className="text-sm text-destructive">
                                    {form.errors.component_id}
                                </p>
                            )}
                        </div>

                        {isPercentage ? (
                            <div className="grid gap-2">
                                <Label htmlFor="rate">Persentase (%)</Label>
                                <Input
                                    id="rate"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={form.data.rate}
                                    onChange={(event) =>
                                        form.setData('rate', event.target.value)
                                    }
                                    placeholder="Mis. 10"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Dihitung dari total penghasilan tetap (mis.
                                    gaji pokok) saat payroll.
                                </p>
                                {form.errors.rate && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.rate}
                                    </p>
                                )}
                            </div>
                        ) : (
                            <div className="grid gap-2">
                                <Label htmlFor="amount">Nominal (Rp)</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    min={0}
                                    value={form.data.amount}
                                    onChange={(event) =>
                                        form.setData(
                                            'amount',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Mis. 5000000"
                                />
                                {form.data.amount &&
                                    Number(form.data.amount) > 0 && (
                                        <p className="text-xs text-muted-foreground">
                                            {formatRupiah(
                                                Number(form.data.amount),
                                            )}
                                        </p>
                                    )}
                                {form.errors.amount && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.amount}
                                    </p>
                                )}
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="effective_date">
                                Tanggal Berlaku
                            </Label>
                            <Input
                                id="effective_date"
                                type="date"
                                value={form.data.effective_date}
                                onChange={(event) =>
                                    form.setData(
                                        'effective_date',
                                        event.target.value,
                                    )
                                }
                            />
                            {form.errors.effective_date && (
                                <p className="text-sm text-destructive">
                                    {form.errors.effective_date}
                                </p>
                            )}
                        </div>
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
                            form="salary-component-form"
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
