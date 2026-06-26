import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import overtimeRequests from '@/actions/App/Http/Controllers/OvertimeRequestController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';

type IdOption = { id: number; label: string };

type CreateProps = {
    employees: IdOption[];
};

type OvertimeForm = {
    employee_id: string;
    date: string;
    start_time: string;
    end_time: string;
    day_type: string;
    reason: string;
};

const emptyForm: OvertimeForm = {
    employee_id: '',
    date: '',
    start_time: '',
    end_time: '',
    day_type: 'workday',
    reason: '',
};

export default function OvertimeRequestsCreate({ employees }: CreateProps) {
    useFlashToast();

    const form = useForm<OvertimeForm>(emptyForm);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            reason: data.reason || null,
        }));

        form.post(overtimeRequests.store.url());
    }

    return (
        <>
            <Head title="Ajukan Lembur" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Ajukan Lembur"
                    description="Catat pengajuan lembur karyawan. Persetujuan diproses lewat Inbox Approval."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2 md:col-span-2">
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
                                    <Label htmlFor="date">
                                        Tanggal <RequiredMark />
                                    </Label>
                                    <DatePicker
                                        id="date"
                                        value={form.data.date}
                                        onChange={(value) =>
                                            form.setData('date', value)
                                        }
                                        placeholder="Pilih tanggal"
                                    />
                                    <InputError message={form.errors.date} />
                                </div>

                                <div className="hidden md:block" />

                                <div className="grid gap-2">
                                    <Label htmlFor="start_time">
                                        Jam Mulai <RequiredMark />
                                    </Label>
                                    <Input
                                        id="start_time"
                                        type="time"
                                        value={form.data.start_time}
                                        onChange={(event) =>
                                            form.setData(
                                                'start_time',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.start_time}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="end_time">
                                        Jam Selesai <RequiredMark />
                                    </Label>
                                    <Input
                                        id="end_time"
                                        type="time"
                                        value={form.data.end_time}
                                        onChange={(event) =>
                                            form.setData(
                                                'end_time',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.end_time}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="day_type">
                                        Jenis Hari <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.day_type}
                                        onValueChange={(value) =>
                                            form.setData('day_type', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="day_type"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="workday">
                                                Hari Kerja (1.5x / 2x)
                                            </SelectItem>
                                            <SelectItem value="holiday">
                                                Hari Libur (2x / 3x / 4x)
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.day_type}
                                    />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="reason">Alasan</Label>
                                    <textarea
                                        id="reason"
                                        value={form.data.reason}
                                        onChange={(event) =>
                                            form.setData(
                                                'reason',
                                                event.target.value,
                                            )
                                        }
                                        rows={3}
                                        placeholder="Alasan lembur (opsional)"
                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                    />
                                    <InputError message={form.errors.reason} />
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="secondary" type="button">
                                <Link href={overtimeRequests.index.url()}>
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
