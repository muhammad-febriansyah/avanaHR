import { Head, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Pencil,
    Plus,
    Trash2,
    Wallet,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import leaveBalances from '@/actions/App/Http/Controllers/LeaveBalanceController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
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

type Option = { id: number; name: string };
type EmployeeOption = { id: number; label: string };

type Balance = {
    id: number;
    employee_id: number;
    employee_name: string | null;
    employee_no: string | null;
    leave_type_id: number;
    leave_type_name: string | null;
    year: number;
    entitled: number;
    used: number;
    pending: number;
    expired: number;
    available: number;
};

type Filters = {
    search?: string;
    year?: string;
    leave_type_id?: string;
};

type IndexProps = {
    balances: Paginator<Balance>;
    filters: Filters;
    currentYear: number;
    options: {
        leaveTypes: Option[];
        employees: EmployeeOption[];
        years: number[];
    };
};

const ALL_TYPES = 'all';

type BalanceForm = {
    employee_id: string;
    leave_type_id: string;
    year: string;
    entitled: string;
    used: string;
    pending: string;
    expired: string;
};

function emptyForm(year: number): BalanceForm {
    return {
        employee_id: '',
        leave_type_id: '',
        year: String(year),
        entitled: '0',
        used: '0',
        pending: '0',
        expired: '0',
    };
}

function num(value: string): number {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : 0;
}

export default function LeaveBalancesIndex({
    balances: paginator,
    filters = {},
    currentYear,
    options,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [year, setYear] = useState(filters.year ?? String(currentYear));
    const [leaveType, setLeaveType] = useState(
        filters.leave_type_id ?? ALL_TYPES,
    );
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            leaveBalances.index.url(),
            {
                search: search || undefined,
                year,
                leave_type_id: leaveType === ALL_TYPES ? undefined : leaveType,
                ...extra,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    // Debounce free-text search; immediate filters call go() directly.
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
    const [editing, setEditing] = useState<Balance | null>(null);
    const form = useForm<BalanceForm>(emptyForm(currentYear));

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm(Number(year)));
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Balance) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            employee_id: String(item.employee_id),
            leave_type_id: String(item.leave_type_id),
            year: String(item.year),
            entitled: String(item.entitled),
            used: String(item.used),
            pending: String(item.pending),
            expired: String(item.expired),
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(leaveBalances.update.url(editing.id), opts);
        } else {
            form.post(leaveBalances.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(leaveBalances.destroy.url(id), { preserveScroll: true });
    }

    const availablePreview =
        num(form.data.entitled) -
        num(form.data.used) -
        num(form.data.pending) -
        num(form.data.expired);

    const rows = paginator.data;

    return (
        <>
            <Head title="Saldo Cuti" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Saldo Cuti"
                    description="Kelola hak dan sisa saldo cuti karyawan per tahun."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Saldo
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
                                value={year}
                                onValueChange={(value) => {
                                    setYear(value);
                                    go({ year: value });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-36">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.years.map((option) => (
                                        <SelectItem
                                            key={option}
                                            value={String(option)}
                                        >
                                            {option}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={leaveType}
                                onValueChange={(value) => {
                                    setLeaveType(value);
                                    go({
                                        leave_type_id:
                                            value === ALL_TYPES
                                                ? undefined
                                                : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-52">
                                    <SelectValue placeholder="Semua Jenis Cuti" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_TYPES}>
                                        Semua Jenis Cuti
                                    </SelectItem>
                                    {options.leaveTypes.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={String(option.id)}
                                        >
                                            {option.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Jenis Cuti</TableHead>
                                        <TableHead className="text-right">
                                            Hak
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Terpakai
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Menunggu
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Hangus
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Sisa
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
                                                colSpan={9}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <Wallet className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada saldo cuti
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item, index) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="text-muted-foreground tabular-nums">
                                                    {(paginator.from ?? 1) +
                                                        index}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {item.employee_name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {item.leave_type_name}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.entitled}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.used}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.pending}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.expired}
                                                </TableCell>
                                                <TableCell className="text-right font-semibold">
                                                    {item.available}
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
                                                            title="Hapus Saldo Cuti"
                                                            description={`Yakin ingin menghapus saldo cuti "${item.employee_name}"?`}
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
                                {paginator.total} saldo · Tahun {year}
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="secondary"
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
                                    variant="secondary"
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
                            {editing ? 'Ubah Saldo Cuti' : 'Tambah Saldo Cuti'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="balance-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        {editing ? (
                            <div className="rounded-lg border bg-muted/40 p-3 text-sm">
                                <div className="font-medium">
                                    {editing.employee_name}
                                </div>
                                <div className="text-muted-foreground">
                                    {editing.leave_type_name} · {editing.year}
                                </div>
                            </div>
                        ) : (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="bal-employee">
                                        Karyawan
                                    </Label>
                                    <Combobox
                                        id="bal-employee"
                                        value={form.data.employee_id}
                                        onChange={(value) =>
                                            form.setData('employee_id', value)
                                        }
                                        options={options.employees.map(
                                            (option) => ({
                                                value: String(option.id),
                                                label: option.label,
                                            }),
                                        )}
                                        placeholder="Pilih karyawan"
                                        searchPlaceholder="Cari karyawan…"
                                        emptyText="Karyawan tidak ditemukan"
                                    />
                                    {form.errors.employee_id && (
                                        <p className="text-sm text-destructive">
                                            {form.errors.employee_id}
                                        </p>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="bal-type">
                                            Jenis Cuti
                                        </Label>
                                        <Select
                                            value={form.data.leave_type_id}
                                            onValueChange={(value) =>
                                                form.setData(
                                                    'leave_type_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                id="bal-type"
                                                className="w-full"
                                            >
                                                <SelectValue placeholder="Pilih" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.leaveTypes.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.id}
                                                            value={String(
                                                                option.id,
                                                            )}
                                                        >
                                                            {option.name}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        {form.errors.leave_type_id && (
                                            <p className="text-sm text-destructive">
                                                {form.errors.leave_type_id}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="bal-year">Tahun</Label>
                                        <Select
                                            value={form.data.year}
                                            onValueChange={(value) =>
                                                form.setData('year', value)
                                            }
                                        >
                                            <SelectTrigger
                                                id="bal-year"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.years.map((option) => (
                                                    <SelectItem
                                                        key={option}
                                                        value={String(option)}
                                                    >
                                                        {option}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {form.errors.year && (
                                            <p className="text-sm text-destructive">
                                                {form.errors.year}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </>
                        )}

                        <div className="grid grid-cols-2 gap-3">
                            <NumberField
                                id="bal-entitled"
                                label="Hak Cuti"
                                value={form.data.entitled}
                                onChange={(value) =>
                                    form.setData('entitled', value)
                                }
                                error={form.errors.entitled}
                            />
                            <NumberField
                                id="bal-used"
                                label="Terpakai"
                                value={form.data.used}
                                onChange={(value) =>
                                    form.setData('used', value)
                                }
                                error={form.errors.used}
                            />
                            <NumberField
                                id="bal-pending"
                                label="Menunggu"
                                value={form.data.pending}
                                onChange={(value) =>
                                    form.setData('pending', value)
                                }
                                error={form.errors.pending}
                            />
                            <NumberField
                                id="bal-expired"
                                label="Hangus"
                                value={form.data.expired}
                                onChange={(value) =>
                                    form.setData('expired', value)
                                }
                                error={form.errors.expired}
                            />
                        </div>

                        <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2 text-sm">
                            <span className="text-muted-foreground">
                                Sisa saldo
                            </span>
                            <span className="font-semibold">
                                {availablePreview}
                            </span>
                        </div>
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
                            form="balance-form"
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
                max={9999}
                step="0.5"
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
