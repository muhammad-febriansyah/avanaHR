import { Head, router, useForm } from '@inertiajs/react';
import {
    Check,
    ChevronLeft,
    ChevronRight,
    HandCoins,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import employeeLoans from '@/actions/App/Http/Controllers/EmployeeLoanController';
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
    principal: number;
    tenor_months: number;
    installment: number;
    outstanding: number;
    status: string;
};

type Filters = { search?: string; status?: string };

type IndexProps = {
    loans: Paginator<Row>;
    filters: Filters;
    statuses: Option[];
    options: { employees: EmployeeOption[] };
};

const ALL_STATUS = 'all';

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

type LoanForm = {
    employee_id: string;
    principal: string;
    tenor_months: string;
};

const emptyForm: LoanForm = {
    employee_id: '',
    principal: '',
    tenor_months: '12',
};

export default function EmployeeLoansIndex({
    loans: paginator,
    filters = {},
    statuses,
    options,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? ALL_STATUS);
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            employeeLoans.index.url(),
            {
                search: search || undefined,
                status: status === ALL_STATUS ? undefined : status,
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
    const form = useForm<LoanForm>(emptyForm);

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
            principal: String(item.principal),
            tenor_months: String(item.tenor_months),
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            principal: Number(data.principal),
            tenor_months: Number(data.tenor_months),
        }));

        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(employeeLoans.update.url(editing.id), opts);
        } else {
            form.post(employeeLoans.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(employeeLoans.destroy.url(id), { preserveScroll: true });
    }

    function decide(id: number, decision: 'approved' | 'rejected') {
        router.patch(
            employeeLoans.decide.url(id),
            { status: decision },
            { preserveScroll: true },
        );
    }

    const principalNum = Number(form.data.principal);
    const tenorNum = Number(form.data.tenor_months);
    const installmentPreview =
        principalNum > 0 && tenorNum > 0
            ? Math.ceil(principalNum / tenorNum)
            : 0;

    const rows = paginator.data;

    return (
        <>
            <Head title="Pinjaman" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Pinjaman"
                    description="Kelola pinjaman karyawan dan cicilannya."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Pinjaman
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
                                        <TableHead className="text-right">
                                            Pokok
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Tenor
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Cicilan/bln
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Sisa
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
                                                colSpan={7}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <HandCoins className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada pinjaman
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
                                                <TableCell className="text-right">
                                                    {rupiah.format(
                                                        item.principal,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.tenor_months} bln
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {rupiah.format(
                                                        item.installment,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {rupiah.format(
                                                        item.outstanding,
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
                                                            <>
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    className="text-emerald-700 hover:text-emerald-700 dark:text-emerald-400"
                                                                    onClick={() =>
                                                                        decide(
                                                                            item.id,
                                                                            'approved',
                                                                        )
                                                                    }
                                                                >
                                                                    <Check />
                                                                    Setujui
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    className="text-red-700 hover:text-red-700 dark:text-red-400"
                                                                    onClick={() =>
                                                                        decide(
                                                                            item.id,
                                                                            'rejected',
                                                                        )
                                                                    }
                                                                >
                                                                    <X />
                                                                    Tolak
                                                                </Button>
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
                                                            </>
                                                        ) : null}
                                                        <ConfirmDialog
                                                            title="Hapus Pinjaman"
                                                            description={`Yakin ingin menghapus pinjaman "${item.employee_name}"?`}
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
                                {paginator.total} pinjaman
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
                            {editing ? 'Ubah Pinjaman' : 'Tambah Pinjaman'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="loan-form"
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
                                <Label htmlFor="el-employee">Karyawan</Label>
                                <Select
                                    value={form.data.employee_id}
                                    onValueChange={(value) =>
                                        form.setData('employee_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="el-employee"
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
                                <Label htmlFor="el-principal">Pokok (Rp)</Label>
                                <Input
                                    id="el-principal"
                                    type="number"
                                    min={1}
                                    value={form.data.principal}
                                    onChange={(event) =>
                                        form.setData(
                                            'principal',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Mis. 6000000"
                                />
                                {form.errors.principal && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.principal}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="el-tenor">Tenor (bulan)</Label>
                                <Input
                                    id="el-tenor"
                                    type="number"
                                    min={1}
                                    max={120}
                                    value={form.data.tenor_months}
                                    onChange={(event) =>
                                        form.setData(
                                            'tenor_months',
                                            event.target.value,
                                        )
                                    }
                                />
                                {form.errors.tenor_months && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.tenor_months}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2 text-sm">
                            <span className="text-muted-foreground">
                                Cicilan per bulan
                            </span>
                            <span className="font-semibold">
                                {rupiah.format(installmentPreview)}
                            </span>
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
                            form="loan-form"
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
