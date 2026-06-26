import { Head, router, useForm } from '@inertiajs/react';
import { CalendarPlus, CalendarRange, Trash2, Wand2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import schedules from '@/actions/App/Http/Controllers/ScheduleController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
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

type Option = { id: number; label: string };

type Row = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    date: string | null;
    shift_code: string | null;
    shift_name: string | null;
    status: string | null;
};

type Paginated<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type IndexProps = {
    schedules: Paginated<Row>;
    filters: {
        employee_id?: string;
        shift_id?: string;
        date_from?: string;
        date_to?: string;
    };
    options: { employees: Option[]; shifts: Option[]; patterns: Option[] };
};

export default function SchedulesIndex({
    schedules: rows,
    filters,
    options,
}: IndexProps) {
    const [assignOpen, setAssignOpen] = useState(false);
    const [generateOpen, setGenerateOpen] = useState(false);

    const assign = useForm({ employee_id: '', shift_id: '', date: '' });
    const generate = useForm<{
        pattern_id: string;
        employee_ids: number[];
        start_date: string;
        end_date: string;
    }>({ pattern_id: '', employee_ids: [], start_date: '', end_date: '' });

    function applyFilter(key: string, value: string) {
        router.get(
            schedules.index.url(),
            { ...filters, [key]: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function submitAssign(event: FormEvent) {
        event.preventDefault();
        assign.post(schedules.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                assign.reset();
                setAssignOpen(false);
            },
        });
    }

    function toggleEmployee(id: number) {
        generate.setData(
            'employee_ids',
            generate.data.employee_ids.includes(id)
                ? generate.data.employee_ids.filter((e) => e !== id)
                : [...generate.data.employee_ids, id],
        );
    }

    function submitGenerate(event: FormEvent) {
        event.preventDefault();
        generate.post(schedules.generate.url(), {
            preserveScroll: true,
            onSuccess: () => {
                generate.reset();
                setGenerateOpen(false);
            },
        });
    }

    return (
        <>
            <Head title="Roster Shift" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Roster Shift"
                    description="Jadwal shift karyawan per tanggal. Buat manual atau generate dari pola."
                >
                    <Button
                        variant="outline"
                        onClick={() => setGenerateOpen(true)}
                    >
                        <Wand2 />
                        Generate dari Pola
                    </Button>
                    <Button onClick={() => setAssignOpen(true)}>
                        <CalendarPlus />
                        Assign Shift
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="grid gap-3 p-5 md:grid-cols-4">
                        <Select
                            value={filters.employee_id ?? ''}
                            onValueChange={(v) =>
                                applyFilter('employee_id', v === 'all' ? '' : v)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Semua karyawan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua karyawan
                                </SelectItem>
                                {options.employees.map((e) => (
                                    <SelectItem key={e.id} value={String(e.id)}>
                                        {e.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={filters.shift_id ?? ''}
                            onValueChange={(v) =>
                                applyFilter('shift_id', v === 'all' ? '' : v)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Semua shift" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua shift</SelectItem>
                                {options.shifts.map((s) => (
                                    <SelectItem key={s.id} value={String(s.id)}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <DatePicker
                            value={filters.date_from ?? ''}
                            onChange={(v) => applyFilter('date_from', v)}
                            placeholder="Dari tanggal"
                        />
                        <DatePicker
                            value={filters.date_to ?? ''}
                            onChange={(v) => applyFilter('date_to', v)}
                            placeholder="Sampai tanggal"
                        />
                    </CardContent>
                </Card>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Karyawan</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Shift</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            <CalendarRange className="mx-auto mb-2 size-6 opacity-50" />
                                            Belum ada jadwal
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    rows.data.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell className="font-medium text-navy">
                                                {row.employee_name}
                                                <span className="block text-xs text-muted-foreground">
                                                    {row.employee_no}
                                                </span>
                                            </TableCell>
                                            <TableCell>{row.date}</TableCell>
                                            <TableCell>
                                                {row.shift_code} —{' '}
                                                {row.shift_name}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ConfirmDialog
                                                    title="Hapus jadwal?"
                                                    description={`Jadwal ${row.employee_name} pada ${row.date} akan dihapus.`}
                                                    confirmLabel="Hapus"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            schedules.destroy.url(
                                                                row.id,
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

            {/* Assign single shift */}
            <Dialog open={assignOpen} onOpenChange={setAssignOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Assign Shift</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitAssign} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label>
                                Karyawan <RequiredMark />
                            </Label>
                            <Select
                                value={assign.data.employee_id}
                                onValueChange={(v) =>
                                    assign.setData('employee_id', v)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih karyawan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.employees.map((e) => (
                                        <SelectItem
                                            key={e.id}
                                            value={String(e.id)}
                                        >
                                            {e.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {assign.errors.employee_id && (
                                <p className="text-sm text-destructive">
                                    {assign.errors.employee_id}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label>
                                Shift <RequiredMark />
                            </Label>
                            <Select
                                value={assign.data.shift_id}
                                onValueChange={(v) =>
                                    assign.setData('shift_id', v)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih shift" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.shifts.map((s) => (
                                        <SelectItem
                                            key={s.id}
                                            value={String(s.id)}
                                        >
                                            {s.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {assign.errors.shift_id && (
                                <p className="text-sm text-destructive">
                                    {assign.errors.shift_id}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label>
                                Tanggal <RequiredMark />
                            </Label>
                            <DatePicker
                                value={assign.data.date}
                                onChange={(v) => assign.setData('date', v)}
                                placeholder="Pilih tanggal"
                            />
                            {assign.errors.date && (
                                <p className="text-sm text-destructive">
                                    {assign.errors.date}
                                </p>
                            )}
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setAssignOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={assign.processing}>
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Generate from pattern */}
            <Dialog open={generateOpen} onOpenChange={setGenerateOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Generate Roster dari Pola</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitGenerate} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label>
                                Pola Shift <RequiredMark />
                            </Label>
                            <Select
                                value={generate.data.pattern_id}
                                onValueChange={(v) =>
                                    generate.setData('pattern_id', v)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih pola" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.patterns.map((p) => (
                                        <SelectItem
                                            key={p.id}
                                            value={String(p.id)}
                                        >
                                            {p.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {generate.errors.pattern_id && (
                                <p className="text-sm text-destructive">
                                    {generate.errors.pattern_id}
                                </p>
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>
                                    Mulai <RequiredMark />
                                </Label>
                                <DatePicker
                                    value={generate.data.start_date}
                                    onChange={(v) =>
                                        generate.setData('start_date', v)
                                    }
                                    placeholder="Tanggal mulai"
                                />
                                {generate.errors.start_date && (
                                    <p className="text-sm text-destructive">
                                        {generate.errors.start_date}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label>
                                    Selesai <RequiredMark />
                                </Label>
                                <DatePicker
                                    value={generate.data.end_date}
                                    onChange={(v) =>
                                        generate.setData('end_date', v)
                                    }
                                    placeholder="Tanggal selesai"
                                />
                                {generate.errors.end_date && (
                                    <p className="text-sm text-destructive">
                                        {generate.errors.end_date}
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label>
                                Karyawan <RequiredMark />
                            </Label>
                            <div className="max-h-48 overflow-y-auto rounded-md border border-border p-2">
                                {options.employees.map((e) => (
                                    <label
                                        key={e.id}
                                        className="flex items-center gap-2 rounded px-1 py-1 text-sm hover:bg-muted"
                                    >
                                        <Checkbox
                                            checked={generate.data.employee_ids.includes(
                                                e.id,
                                            )}
                                            onCheckedChange={() =>
                                                toggleEmployee(e.id)
                                            }
                                        />
                                        {e.label}
                                    </label>
                                ))}
                            </div>
                            {generate.errors.employee_ids && (
                                <p className="text-sm text-destructive">
                                    {generate.errors.employee_ids}
                                </p>
                            )}
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setGenerateOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={generate.processing}
                            >
                                Generate
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
