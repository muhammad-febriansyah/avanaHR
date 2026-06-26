import { Head, router, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    CalendarOff,
    Check,
    Pencil,
    Plus,
    Star,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import holidaysActions from '@/actions/App/Http/Controllers/HolidayController';
import workCalendars from '@/actions/App/Http/Controllers/WorkCalendarController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DatePicker } from '@/components/ui/date-picker';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

type Calendar = {
    id: number;
    name: string;
    is_default: boolean;
    holidays_count: number;
};

type Holiday = {
    id: number;
    calendar_id: number;
    date: string;
    name: string;
    is_national: boolean;
};

type IndexProps = {
    calendars: Calendar[];
    holidays: Holiday[];
    selectedCalendarId: number | null;
};

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

function formatDate(value: string): string {
    return dateFormatter.format(new Date(value));
}

export default function CalendarsIndex({
    calendars,
    holidays,
    selectedCalendarId,
}: IndexProps) {
    const selected = calendars.find((c) => c.id === selectedCalendarId) ?? null;

    function selectCalendar(id: number) {
        router.get(
            workCalendars.index.url({ query: { calendar: id } }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                only: ['holidays', 'selectedCalendarId'],
            },
        );
    }

    return (
        <>
            <Head title="Kalender Kerja" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Kalender Kerja"
                    description="Kelola kalender kerja dan hari libur perusahaan."
                />

                <div className="grid gap-5 lg:grid-cols-[320px_1fr]">
                    <CalendarList
                        calendars={calendars}
                        selectedId={selectedCalendarId}
                        onSelect={selectCalendar}
                    />
                    <HolidayPanel calendar={selected} holidays={holidays} />
                </div>
            </div>
        </>
    );
}

function CalendarList({
    calendars,
    selectedId,
    onSelect,
}: {
    calendars: Calendar[];
    selectedId: number | null;
    onSelect: (id: number) => void;
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Calendar | null>(null);
    const form = useForm({ name: '', is_default: false });

    function openCreate() {
        setEditing(null);
        form.setDefaults({ name: '', is_default: false });
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Calendar) {
        setEditing(item);
        form.clearErrors();
        form.setData({ name: item.name, is_default: item.is_default });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(workCalendars.update.url(editing.id), opts);
        } else {
            form.post(workCalendars.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(workCalendars.destroy.url(id), { preserveScroll: true });
    }

    function setDefault(id: number) {
        router.put(
            workCalendars.setDefault.url(id),
            {},
            { preserveScroll: true },
        );
    }

    return (
        <Card className="gap-0 py-0">
            <CardContent className="p-5">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-sm font-semibold">Kalender</h2>
                    <Button size="sm" onClick={openCreate}>
                        <Plus />
                        Tambah
                    </Button>
                </div>

                {calendars.length === 0 ? (
                    <EmptyState
                        icon={<CalendarDays className="size-6" />}
                        text="Belum ada kalender"
                    />
                ) : (
                    <div className="flex flex-col gap-2">
                        {calendars.map((item) => (
                            <div
                                key={item.id}
                                role="button"
                                tabIndex={0}
                                onClick={() => onSelect(item.id)}
                                onKeyDown={(event) => {
                                    if (
                                        event.key === 'Enter' ||
                                        event.key === ' '
                                    ) {
                                        event.preventDefault();
                                        onSelect(item.id);
                                    }
                                }}
                                className={cn(
                                    'cursor-pointer rounded-lg border p-3 transition-colors',
                                    item.id === selectedId
                                        ? 'border-primary bg-primary/5'
                                        : 'hover:bg-muted/50',
                                )}
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <span className="truncate text-sm font-medium">
                                                {item.name}
                                            </span>
                                            {item.is_default && (
                                                <Badge variant="secondary">
                                                    Default
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            {item.holidays_count} hari libur
                                        </p>
                                    </div>
                                </div>
                                <div className="mt-3 flex items-center gap-2">
                                    {!item.is_default && (
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            onClick={(event) => {
                                                event.stopPropagation();
                                                setDefault(item.id);
                                            }}
                                        >
                                            <Star />
                                            Default
                                        </Button>
                                    )}
                                    <Button
                                        size="sm"
                                        variant="success"
                                        onClick={(event) => {
                                            event.stopPropagation();
                                            openEdit(item);
                                        }}
                                    >
                                        <Pencil />
                                        Edit
                                    </Button>
                                    {!item.is_default && (
                                        <ConfirmDialog
                                            title="Hapus Kalender"
                                            description={`Yakin ingin menghapus "${item.name}"? Semua hari libur di kalender ini akan ikut terhapus.`}
                                            confirmLabel="Hapus"
                                            onConfirm={() =>
                                                handleDelete(item.id)
                                            }
                                            trigger={
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    onClick={(event) =>
                                                        event.stopPropagation()
                                                    }
                                                >
                                                    <Trash2 />
                                                    Hapus
                                                </Button>
                                            }
                                        />
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Ubah Kalender' : 'Tambah Kalender'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="calendar-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="cal-name">Nama</Label>
                            <Input
                                id="cal-name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                placeholder="Mis. Kalender Nasional"
                                autoFocus
                            />
                            {form.errors.name && (
                                <p className="text-sm text-destructive">
                                    {form.errors.name}
                                </p>
                            )}
                        </div>

                        <label className="flex items-center gap-2.5 text-sm">
                            <Checkbox
                                checked={form.data.is_default}
                                onCheckedChange={(value) =>
                                    form.setData('is_default', value === true)
                                }
                            />
                            Jadikan kalender default
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
                            form="calendar-form"
                            disabled={form.processing}
                        >
                            {editing ? 'Simpan Perubahan' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
}

function HolidayPanel({
    calendar,
    holidays,
}: {
    calendar: Calendar | null;
    holidays: Holiday[];
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Holiday | null>(null);
    const form = useForm({
        calendar_id: 0,
        date: '',
        name: '',
        is_national: true,
    });

    function openCreate() {
        if (!calendar) {
            return;
        }

        setEditing(null);
        form.setDefaults({
            calendar_id: calendar.id,
            date: '',
            name: '',
            is_national: true,
        });
        form.reset();
        form.setData('calendar_id', calendar.id);
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Holiday) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            calendar_id: item.calendar_id,
            date: item.date.slice(0, 10),
            name: item.name,
            is_national: item.is_national,
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(holidaysActions.update.url(editing.id), opts);
        } else {
            form.post(holidaysActions.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(holidaysActions.destroy.url(id), {
            preserveScroll: true,
        });
    }

    return (
        <Card className="gap-0 py-0">
            <CardContent className="p-5">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-sm font-semibold">
                        Hari Libur
                        {calendar && (
                            <span className="ml-2 font-normal text-muted-foreground">
                                · {calendar.name}
                            </span>
                        )}
                    </h2>
                    <Button size="sm" onClick={openCreate} disabled={!calendar}>
                        <Plus />
                        Tambah Hari Libur
                    </Button>
                </div>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">No</TableHead>
                                <TableHead>Tanggal</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>Nasional</TableHead>
                                <TableHead className="text-right">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {holidays.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-12">
                                        <EmptyState
                                            icon={
                                                <CalendarOff className="size-6" />
                                            }
                                            text={
                                                calendar
                                                    ? 'Belum ada hari libur'
                                                    : 'Pilih kalender terlebih dahulu'
                                            }
                                        />
                                    </TableCell>
                                </TableRow>
                            ) : (
                                holidays.map((item, index) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="text-muted-foreground tabular-nums">
                                            {index + 1}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {formatDate(item.date)}
                                        </TableCell>
                                        <TableCell>{item.name}</TableCell>
                                        <TableCell>
                                            {item.is_national ? (
                                                <Check className="size-4 text-primary" />
                                            ) : (
                                                '-'
                                            )}
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
                                                    title="Hapus Hari Libur"
                                                    description={`Yakin ingin menghapus "${item.name}"?`}
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
            </CardContent>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Ubah Hari Libur' : 'Tambah Hari Libur'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="holiday-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="holiday-date">Tanggal</Label>
                            <DatePicker
                                id="holiday-date"
                                value={form.data.date}
                                onChange={(value) =>
                                    form.setData('date', value)
                                }
                            />
                            {form.errors.date && (
                                <p className="text-sm text-destructive">
                                    {form.errors.date}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="holiday-name">Nama</Label>
                            <Input
                                id="holiday-name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                placeholder="Mis. Hari Kemerdekaan"
                            />
                            {form.errors.name && (
                                <p className="text-sm text-destructive">
                                    {form.errors.name}
                                </p>
                            )}
                        </div>

                        <label className="flex items-center gap-2.5 text-sm">
                            <Checkbox
                                checked={form.data.is_national}
                                onCheckedChange={(value) =>
                                    form.setData('is_national', value === true)
                                }
                            />
                            Hari libur nasional
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
                            form="holiday-form"
                            disabled={form.processing}
                        >
                            {editing ? 'Simpan Perubahan' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
}

function EmptyState({ icon, text }: { icon: React.ReactNode; text: string }) {
    return (
        <div className="flex flex-col items-center justify-center gap-3 py-8 text-center">
            <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                {icon}
            </div>
            <p className="text-sm text-muted-foreground">{text}</p>
        </div>
    );
}
