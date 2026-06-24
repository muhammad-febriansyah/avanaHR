import { Head, router, useForm } from '@inertiajs/react';
import {
    CalendarRange,
    Check,
    ChevronLeft,
    ChevronRight,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import leaveRequests from '@/actions/App/Http/Controllers/LeaveRequestController';
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

type Option = { id: number; name: string };
type EmployeeOption = { id: number; label: string };
type StatusOption = { value: string; label: string };

type LeaveRow = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    leave_type_name: string | null;
    start_date: string | null;
    end_date: string | null;
    days: number;
    reason: string | null;
    status: string;
};

type Filters = { search?: string; status?: string; leave_type_id?: string };

type IndexProps = {
    requests: Paginator<LeaveRow>;
    filters: Filters;
    statuses: StatusOption[];
    options: { leaveTypes: Option[]; employees: EmployeeOption[] };
};

const ALL_STATUS = 'all';
const ALL_TYPES = 'all';

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : '-';
}

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

type LeaveForm = {
    employee_id: string;
    leave_type_id: string;
    start_date: string;
    end_date: string;
    reason: string;
};

const emptyForm: LeaveForm = {
    employee_id: '',
    leave_type_id: '',
    start_date: '',
    end_date: '',
    reason: '',
};

function dayCount(start: string, end: string): number {
    if (!start || !end) {
        return 0;
    }

    const diff =
        (new Date(end).getTime() - new Date(start).getTime()) / 86_400_000;

    return diff < 0 ? 0 : Math.round(diff) + 1;
}

export default function LeaveRequestsIndex({
    requests: paginator,
    filters = {},
    statuses,
    options,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? ALL_STATUS);
    const [leaveType, setLeaveType] = useState(
        filters.leave_type_id ?? ALL_TYPES,
    );
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            leaveRequests.index.url(),
            {
                search: search || undefined,
                status: status === ALL_STATUS ? undefined : status,
                leave_type_id: leaveType === ALL_TYPES ? undefined : leaveType,
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
    const [editing, setEditing] = useState<LeaveRow | null>(null);
    const form = useForm<LeaveForm>(emptyForm);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: LeaveRow) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            employee_id: '',
            leave_type_id: '',
            start_date: item.start_date ?? '',
            end_date: item.end_date ?? '',
            reason: item.reason ?? '',
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(leaveRequests.update.url(editing.id), opts);
        } else {
            form.post(leaveRequests.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(leaveRequests.destroy.url(id), { preserveScroll: true });
    }

    function decide(id: number, decision: 'approved' | 'rejected') {
        router.patch(
            leaveRequests.decide.url(id),
            { status: decision },
            { preserveScroll: true },
        );
    }

    const previewDays = dayCount(form.data.start_date, form.data.end_date);
    const rows = paginator.data;

    return (
        <>
            <Head title="Pengajuan Cuti" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Pengajuan Cuti"
                    description="Kelola dan setujui pengajuan cuti karyawan."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Ajukan Cuti
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
                                <SelectTrigger className="w-full sm:w-48">
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
                                        <TableHead>Jenis Cuti</TableHead>
                                        <TableHead>Periode</TableHead>
                                        <TableHead className="text-right">
                                            Hari
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
                                                colSpan={6}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <CalendarRange className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada pengajuan cuti
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
                                                    {item.leave_type_name}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDate(
                                                        item.start_date,
                                                    )}{' '}
                                                    – {formatDate(item.end_date)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.days}
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
                                                            title="Hapus Pengajuan Cuti"
                                                            description={`Yakin ingin menghapus pengajuan cuti "${item.employee_name}"?`}
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
                                {paginator.total} pengajuan
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
                            {editing ? 'Ubah Pengajuan Cuti' : 'Ajukan Cuti'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="leave-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        {editing ? (
                            <div className="rounded-lg border bg-muted/40 p-3 text-sm">
                                <div className="font-medium">
                                    {editing.employee_name}
                                </div>
                                <div className="text-muted-foreground">
                                    {editing.leave_type_name}
                                </div>
                            </div>
                        ) : (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="lr-employee">Karyawan</Label>
                                    <Select
                                        value={form.data.employee_id}
                                        onValueChange={(value) =>
                                            form.setData('employee_id', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="lr-employee"
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

                                <div className="grid gap-2">
                                    <Label htmlFor="lr-type">Jenis Cuti</Label>
                                    <Select
                                        value={form.data.leave_type_id}
                                        onValueChange={(value) =>
                                            form.setData('leave_type_id', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="lr-type"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Pilih jenis cuti" />
                                        </SelectTrigger>
                                        <SelectContent>
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
                                    {form.errors.leave_type_id && (
                                        <p className="text-sm text-destructive">
                                            {form.errors.leave_type_id}
                                        </p>
                                    )}
                                </div>
                            </>
                        )}

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="lr-start">Mulai</Label>
                                <Input
                                    id="lr-start"
                                    type="date"
                                    value={form.data.start_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'start_date',
                                            event.target.value,
                                        )
                                    }
                                />
                                {form.errors.start_date && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.start_date}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="lr-end">Selesai</Label>
                                <Input
                                    id="lr-end"
                                    type="date"
                                    value={form.data.end_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'end_date',
                                            event.target.value,
                                        )
                                    }
                                />
                                {form.errors.end_date && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.end_date}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="lr-reason">Alasan</Label>
                            <textarea
                                id="lr-reason"
                                value={form.data.reason}
                                onChange={(event) =>
                                    form.setData('reason', event.target.value)
                                }
                                rows={3}
                                placeholder="Alasan cuti (opsional)"
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            />
                            {form.errors.reason && (
                                <p className="text-sm text-destructive">
                                    {form.errors.reason}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2 text-sm">
                            <span className="text-muted-foreground">
                                Total hari
                            </span>
                            <span className="font-semibold">{previewDays}</span>
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
                            form="leave-form"
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
