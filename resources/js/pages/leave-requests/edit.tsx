import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import leaveRequests from '@/actions/App/Http/Controllers/LeaveRequestController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import { useFlashToast } from '@/hooks/use-flash-toast';

type LeaveRequestEntity = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    leave_type_name: string | null;
    start_date: string | null;
    end_date: string | null;
    reason: string | null;
};

type Balance = {
    entitled: number;
    used: number;
    available: number;
};

type EditProps = {
    leaveRequest: LeaveRequestEntity;
    balance: Balance | null;
};

type LeaveForm = {
    start_date: string;
    end_date: string;
    reason: string;
};

function dayCount(start: string, end: string): number {
    if (!start || !end) {
        return 0;
    }

    const diff =
        (new Date(end).getTime() - new Date(start).getTime()) / 86_400_000;

    return diff < 0 ? 0 : Math.round(diff) + 1;
}

export default function LeaveRequestsEdit({
    leaveRequest,
    balance,
}: EditProps) {
    useFlashToast();

    const form = useForm<LeaveForm>({
        start_date: leaveRequest.start_date ?? '',
        end_date: leaveRequest.end_date ?? '',
        reason: leaveRequest.reason ?? '',
    });

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            reason: data.reason || null,
        }));

        form.put(leaveRequests.update.url(leaveRequest.id));
    }

    const previewDays = dayCount(form.data.start_date, form.data.end_date);

    return (
        <>
            <Head title="Edit Pengajuan Cuti" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Edit Pengajuan Cuti"
                    description="Perbarui periode dan alasan pengajuan cuti."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="rounded-lg border bg-muted/40 p-3 text-sm">
                                <div className="font-medium">
                                    {leaveRequest.employee_name}
                                </div>
                                <div className="text-muted-foreground">
                                    {leaveRequest.employee_no} ·{' '}
                                    {leaveRequest.leave_type_name}
                                </div>
                            </div>

                            {balance ? (
                                <div className="grid gap-3 rounded-lg border bg-muted/40 p-3 text-sm sm:grid-cols-3">
                                    <div>
                                        <div className="text-muted-foreground">
                                            Jatah
                                        </div>
                                        <div className="font-semibold">
                                            {balance.entitled} hari
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-muted-foreground">
                                            Terpakai
                                        </div>
                                        <div className="font-semibold">
                                            {balance.used} hari
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-muted-foreground">
                                            Sisa
                                        </div>
                                        <div className="font-semibold">
                                            {balance.available} hari
                                        </div>
                                    </div>
                                </div>
                            ) : null}

                            <div className="grid gap-5 md:grid-cols-2">
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
