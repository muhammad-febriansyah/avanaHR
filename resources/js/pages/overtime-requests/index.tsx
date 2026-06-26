import { Head, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Clock,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import overtimeRequests from '@/actions/App/Http/Controllers/OvertimeRequestController';
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
type StatusOption = { value: string; label: string };

type OvertimeRow = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    date: string | null;
    start_time: string | null;
    end_time: string | null;
    planned_minutes: number;
    reason: string | null;
    status: string;
};

type Filters = { search?: string; status?: string };

type IndexProps = {
    requests: Paginator<OvertimeRow>;
    filters: Filters;
    statuses: StatusOption[];
    options: { employees: EmployeeOption[] };
};

const ALL_STATUS = 'all';

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : '-';
}

function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    return mins === 0 ? `${hours} jam` : `${hours} jam ${mins} mnt`;
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

type OvertimeForm = {
    employee_id: string;
    date: string;
    start_time: string;
    end_time: string;
    reason: string;
};

const emptyForm: OvertimeForm = {
    employee_id: '',
    date: '',
    start_time: '',
    end_time: '',
    reason: '',
};

export default function OvertimeRequestsIndex({
    requests: paginator,
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
            overtimeRequests.index.url(),
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
    const [editing, setEditing] = useState<OvertimeRow | null>(null);
    const form = useForm<OvertimeForm>(emptyForm);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: OvertimeRow) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            employee_id: '',
            date: item.date ?? '',
            start_time: item.start_time ?? '',
            end_time: item.end_time ?? '',
            reason: item.reason ?? '',
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(overtimeRequests.update.url(editing.id), opts);
        } else {
            form.post(overtimeRequests.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(overtimeRequests.destroy.url(id), {
            preserveScroll: true,
        });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Lembur" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Lembur"
                    description="Ajukan lembur karyawan. Persetujuan diproses lewat Inbox Approval."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Ajukan Lembur
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
                                <SelectTrigger className="w-full sm:w-48">
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
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>Durasi</TableHead>
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
                                                        <Clock className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada pengajuan
                                                        lembur
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
                                                    {formatDate(item.date)}
                                                </TableCell>
                                                <TableCell>
                                                    {item.start_time} –{' '}
                                                    {item.end_time}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDuration(
                                                        item.planned_minutes,
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
                                                            title="Hapus Pengajuan Lembur"
                                                            description={`Yakin ingin menghapus pengajuan lembur "${item.employee_name}"?`}
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
                            {editing
                                ? 'Ubah Pengajuan Lembur'
                                : 'Ajukan Lembur'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="overtime-form"
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
                                <Label htmlFor="ot-employee">Karyawan</Label>
                                <Select
                                    value={form.data.employee_id}
                                    onValueChange={(value) =>
                                        form.setData('employee_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="ot-employee"
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

                        <div className="grid gap-2">
                            <Label htmlFor="ot-date">Tanggal</Label>
                            <Input
                                id="ot-date"
                                type="date"
                                value={form.data.date}
                                onChange={(event) =>
                                    form.setData('date', event.target.value)
                                }
                            />
                            {form.errors.date && (
                                <p className="text-sm text-destructive">
                                    {form.errors.date}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="ot-start">Jam Mulai</Label>
                                <Input
                                    id="ot-start"
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
                                <Label htmlFor="ot-end">Jam Selesai</Label>
                                <Input
                                    id="ot-end"
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

                        <div className="grid gap-2">
                            <Label htmlFor="ot-reason">Alasan</Label>
                            <textarea
                                id="ot-reason"
                                value={form.data.reason}
                                onChange={(event) =>
                                    form.setData('reason', event.target.value)
                                }
                                rows={3}
                                placeholder="Alasan lembur (opsional)"
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            />
                            {form.errors.reason && (
                                <p className="text-sm text-destructive">
                                    {form.errors.reason}
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
                            form="overtime-form"
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
