import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import overtimeRequests from '@/actions/App/Http/Controllers/OvertimeRequestController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
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

type OvertimeRequest = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    date: string | null;
    start_time: string | null;
    end_time: string | null;
    day_type: string | null;
    reason: string | null;
};

type EditProps = {
    overtimeRequest: OvertimeRequest;
};

type OvertimeForm = {
    date: string;
    start_time: string;
    end_time: string;
    day_type: string;
    reason: string;
};

export default function OvertimeRequestsEdit({ overtimeRequest }: EditProps) {
    useFlashToast();

    const form = useForm<OvertimeForm>({
        date: overtimeRequest.date ?? '',
        start_time: overtimeRequest.start_time ?? '',
        end_time: overtimeRequest.end_time ?? '',
        day_type: overtimeRequest.day_type ?? 'workday',
        reason: overtimeRequest.reason ?? '',
    });

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            reason: data.reason || null,
        }));

        form.put(overtimeRequests.update.url(overtimeRequest.id));
    }

    return (
        <>
            <Head title="Edit Pengajuan Lembur" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Edit Pengajuan Lembur"
                    description="Perbarui detail pengajuan lembur karyawan."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="rounded-lg border bg-muted/40 p-3 text-sm">
                                <div className="font-medium">
                                    {overtimeRequest.employee_name}
                                </div>
                                <div className="text-muted-foreground">
                                    {overtimeRequest.employee_no}
                                </div>
                            </div>

                            <div className="grid gap-5 md:grid-cols-2">
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
