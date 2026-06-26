import { Head, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Pencil,
    Plus,
    Receipt,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import reimbursements from '@/actions/App/Http/Controllers/ReimbursementController';
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
import type { Paginator } from '@/types/employee';

type EmployeeOption = { id: number; label: string };
type Option = { value: string; label: string };

type Row = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    category: string;
    amount: number;
    settlement: string;
    status: string;
};

type Filters = { search?: string; status?: string; category?: string };

type IndexProps = {
    reimbursements: Paginator<Row>;
    filters: Filters;
    statuses: Option[];
    categories: Option[];
    settlements: Option[];
    options: { employees: EmployeeOption[] };
};

const ALL_STATUS = 'all';
const ALL_CATEGORY = 'all';

const STATUS_STYLES: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

const STATUS_LABELS: Record<string, string> = {
    pending: 'Pending',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    revision: 'Revisi',
    cancelled: 'Dibatalkan',
};

const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
});

type ReimForm = {
    employee_id: string;
    category: string;
    amount: string;
    settlement: string;
};

const emptyForm: ReimForm = {
    employee_id: '',
    category: 'medical',
    amount: '',
    settlement: 'payroll',
};

function labelOf(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

export default function ReimbursementsIndex({
    reimbursements: paginator,
    filters = {},
    statuses,
    categories,
    settlements,
    options,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? ALL_STATUS);
    const [category, setCategory] = useState(filters.category ?? ALL_CATEGORY);
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            reimbursements.index.url(),
            {
                search: search || undefined,
                status: status === ALL_STATUS ? undefined : status,
                category: category === ALL_CATEGORY ? undefined : category,
                ...extra,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = setTimeout(() => go({}), 350);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Row | null>(null);
    const form = useForm<ReimForm>(emptyForm);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Row) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            employee_id: '',
            category: item.category,
            amount: String(item.amount),
            settlement: item.settlement,
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({ ...data, amount: Number(data.amount) }));

        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(reimbursements.update.url(editing.id), opts);
        } else {
            form.post(reimbursements.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(reimbursements.destroy.url(id), { preserveScroll: true });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Reimbursement" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Reimbursement"
                    description="Kelola klaim penggantian biaya karyawan. Persetujuan dilakukan via Inbox Approval."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Klaim
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Cari nama / NIP karyawan"
                                className="sm:max-w-xs"
                            />
                            <Select
                                value={category}
                                onValueChange={(value) => {
                                    setCategory(value);
                                    go({
                                        category:
                                            value === ALL_CATEGORY
                                                ? undefined
                                                : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="Semua Kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_CATEGORY}>
                                        Semua Kategori
                                    </SelectItem>
                                    {categories.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={status}
                                onValueChange={(value) => {
                                    setStatus(value);
                                    go({
                                        status:
                                            value === ALL_STATUS
                                                ? undefined
                                                : value,
                                    });
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
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead className="text-right">
                                            Nominal
                                        </TableHead>
                                        <TableHead>Penyelesaian</TableHead>
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
                                                        <Receipt className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada klaim
                                                        reimbursement
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {item.employee_name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {labelOf(
                                                        categories,
                                                        item.category,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {rupiah.format(item.amount)}
                                                </TableCell>
                                                <TableCell>
                                                    {labelOf(
                                                        settlements,
                                                        item.settlement,
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
                                                        {STATUS_LABELS[
                                                            item.status
                                                        ] ?? item.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        {item.status ===
                                                        'pending' ? (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() =>
                                                                    openEdit(
                                                                        item,
                                                                    )
                                                                }
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Button>
                                                        ) : null}
                                                        <ConfirmDialog
                                                            title="Hapus Reimbursement"
                                                            description={`Yakin ingin menghapus klaim "${item.employee_name}"?`}
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
                                {paginator.total} klaim
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
                                ? 'Ubah Reimbursement'
                                : 'Tambah Reimbursement'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="reim-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        {editing ? (
                            <div className="rounded-lg border bg-muted/40 p-3 text-sm">
                                <div className="font-medium">
                                    {editing.employee_name}
                                </div>
                                <div className="text-muted-foreground">
                                    {editing.employee_no}
                                </div>
                            </div>
                        ) : (
                            <div className="grid gap-2">
                                <Label htmlFor="rb-employee">Karyawan</Label>
                                <Select
                                    value={form.data.employee_id}
                                    onValueChange={(value) =>
                                        form.setData('employee_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="rb-employee"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih karyawan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.employees.map((option) => (
                                            <SelectItem
                                                key={option.id}
                                                value={String(option.id)}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.employee_id && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.employee_id}
                                    </p>
                                )}
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="rb-category">Kategori</Label>
                                <Select
                                    value={form.data.category}
                                    onValueChange={(value) =>
                                        form.setData('category', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="rb-category"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((option) => (
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
                            <div className="grid gap-2">
                                <Label htmlFor="rb-settlement">
                                    Penyelesaian
                                </Label>
                                <Select
                                    value={form.data.settlement}
                                    onValueChange={(value) =>
                                        form.setData('settlement', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="rb-settlement"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {settlements.map((option) => (
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
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="rb-amount">Nominal (Rp)</Label>
                            <Input
                                id="rb-amount"
                                type="number"
                                min={1}
                                value={form.data.amount}
                                onChange={(event) =>
                                    form.setData('amount', event.target.value)
                                }
                                placeholder="Mis. 500000"
                            />
                            {form.errors.amount && (
                                <p className="text-sm text-destructive">
                                    {form.errors.amount}
                                </p>
                            )}
                            {form.data.amount &&
                                Number(form.data.amount) > 0 && (
                                    <p className="text-xs text-muted-foreground">
                                        {rupiah.format(Number(form.data.amount))}
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
                            form="reim-form"
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
