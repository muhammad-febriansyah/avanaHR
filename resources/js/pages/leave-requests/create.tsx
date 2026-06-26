import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import leaveRequests from '@/actions/App/Http/Controllers/LeaveRequestController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';

type LeaveTypeOption = { id: number; name: string };
type EmployeeOption = { id: number; label: string };

type CreateProps = {
    leaveTypes: LeaveTypeOption[];
    employees: EmployeeOption[];
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

export default function LeaveRequestsCreate({
    leaveTypes,
    employees,
}: CreateProps) {
    useFlashToast();

    const form = useForm<LeaveForm>(emptyForm);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            reason: data.reason || null,
        }));

        form.post(leaveRequests.store.url());
    }

    const previewDays = dayCount(form.data.start_date, form.data.end_date);

    return (
        <>
            <Head title="Ajukan Cuti" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Ajukan Cuti"
                    description="Catat pengajuan cuti karyawan. Persetujuan dilakukan via Inbox Approval."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="employee_id">
                                        Karyawan <RequiredMark />
                                    </Label>
                                    <Combobox
                                        id="employee_id"
                                        value={form.data.employee_id}
                                        onChange={(value) =>
                                            form.setData('employee_id', value)
                                        }
                                        options={employees.map((option) => ({
                                            value: String(option.id),
                                            label: option.label,
                                        }))}
                                        placeholder="Pilih karyawan"
                                        searchPlaceholder="Cari karyawan…"
                                        emptyText="Karyawan tidak ditemukan"
                                    />
                                    <InputError
                                        message={form.errors.employee_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="leave_type_id">
                                        Jenis Cuti <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.leave_type_id}
                                        onValueChange={(value) =>
                                            form.setData('leave_type_id', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="leave_type_id"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Pilih jenis cuti" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {leaveTypes.map((option) => (
                                                <SelectItem
                                                    key={option.id}
                                                    value={String(option.id)}
                                                >
                                                    {option.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.leave_type_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="start_date">
                                        Tanggal Mulai <RequiredMark />
                                    </Label>
                                    <DatePicker
                                        id="start_date"
                                        value={form.data.start_date}
                                        onChange={(value) =>
                                            form.setData('start_date', value)
                                        }
                                        placeholder="Pilih tanggal"
                                    />
                                    <InputError
                                        message={form.errors.start_date}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="end_date">
                                        Tanggal Selesai <RequiredMark />
                                    </Label>
                                    <DatePicker
                                        id="end_date"
                                        value={form.data.end_date}
                                        onChange={(value) =>
                                            form.setData('end_date', value)
                                        }
                                        placeholder="Pilih tanggal"
                                    />
                                    <InputError
                                        message={form.errors.end_date}
                                    />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="reason">Alasan</Label>
                                    <textarea
                                        id="reason"
                                        value={form.data.reason}
                                        onChange={(e) =>
                                            form.setData(
                                                'reason',
                                                e.target.value,
                                            )
                                        }
                                        rows={3}
                                        placeholder="Alasan cuti (opsional)"
                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                    />
                                    <InputError message={form.errors.reason} />
                                </div>

                                <div className="md:col-span-2">
                                    <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2 text-sm">
                                        <span className="text-muted-foreground">
                                            Total hari
                                        </span>
                                        <span className="font-semibold">
                                            {previewDays}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="secondary" type="button">
                                <Link href={leaveRequests.index.url()}>
                                    <X />
                                    Batal
                                </Link>
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                <Save />
                                Simpan
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </>
    );
}
