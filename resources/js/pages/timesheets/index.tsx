import { Head, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import timesheets from '@/actions/App/Http/Controllers/TimesheetController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
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

type TimesheetRow = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    date: string | null;
    hours: number;
    note: string | null;
};

type Filters = { search?: string };

type IndexProps = {
    timesheets: Paginator<TimesheetRow>;
    filters: Filters;
    options: { employees: EmployeeOption[] };
};

type TimesheetForm = {
    employee_id: string;
    date: string;
    hours: string;
    note: string;
};

const emptyForm: TimesheetForm = {
    employee_id: '',
    date: '',
    hours: '',
    note: '',
};

export default function TimesheetsIndex({
    timesheets: paginator,
    filters = {},
    options,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            timesheets.index.url(),
            { search: search || undefined, ...extra },
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
    const [editing, setEditing] = useState<TimesheetRow | null>(null);
    const form = useForm<TimesheetForm>(emptyForm);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: TimesheetRow) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            employee_id: '',
            date: item.date ?? '',
            hours: String(item.hours),
            note: item.note ?? '',
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(timesheets.update.url(editing.id), opts);
        } else {
            form.post(timesheets.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(timesheets.destroy.url(id), { preserveScroll: true });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Timesheet" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Timesheet"
                    description="Catat jam kerja karyawan per proyek atau aktivitas harian."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Timesheet
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama / NIP karyawan"
                            className="sm:max-w-xs"
                        />

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Jam</TableHead>
                                        <TableHead>Catatan</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <ClipboardList className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada timesheet
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
                                                <TableCell>{item.date}</TableCell>
                                                <TableCell>{item.hours} jam</TableCell>
                                                <TableCell className="max-w-[18rem] truncate text-muted-foreground">
                                                    {item.note ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => openEdit(item)}
                                                        >
                                                            <Pencil />
                                                            Edit
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Timesheet"
                                                            description={`Yakin ingin menghapus timesheet "${item.employee_name}" (${item.date})?`}
                                                            confirmLabel="Hapus"
                                                            onConfirm={() =>
                                                                handleDelete(item.id)
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
                                {paginator.total} timesheet
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
                                            { preserveState: true, preserveScroll: true },
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
                                            { preserveState: true, preserveScroll: true },
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
                            {editing ? 'Ubah Timesheet' : 'Tambah Timesheet'}
                        </DialogTitle>
                    </DialogHeader>

                    <form id="timesheet-form" onSubmit={handleSubmit} className="grid gap-4">
                        {editing ? (
                            <div className="rounded-lg border bg-muted/40 p-3 text-sm">
                                <div className="font-medium">{editing.employee_name}</div>
                                <div className="text-muted-foreground">
                                    {editing.employee_no}
                                </div>
                            </div>
                        ) : (
                            <div className="grid gap-2">
                                <Label htmlFor="ts-employee">Karyawan</Label>
                                <Select
                                    value={form.data.employee_id}
                                    onValueChange={(value) =>
                                        form.setData('employee_id', value)
                                    }
                                >
                                    <SelectTrigger id="ts-employee" className="w-full">
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
                                <Label htmlFor="ts-date">Tanggal</Label>
                                <Input
                                    id="ts-date"
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
                            <div className="grid gap-2">
                                <Label htmlFor="ts-hours">Jam Kerja</Label>
                                <Input
                                    id="ts-hours"
                                    type="number"
                                    step="0.5"
                                    min="0.5"
                                    max="24"
                                    value={form.data.hours}
                                    onChange={(event) =>
                                        form.setData('hours', event.target.value)
                                    }
                                />
                                {form.errors.hours && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.hours}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="ts-note">Catatan</Label>
                            <textarea
                                id="ts-note"
                                value={form.data.note}
                                onChange={(event) => form.setData('note', event.target.value)}
                                rows={3}
                                placeholder="Aktivitas / proyek (opsional)"
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            />
                            {form.errors.note && (
                                <p className="text-sm text-destructive">
                                    {form.errors.note}
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
                            form="timesheet-form"
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
