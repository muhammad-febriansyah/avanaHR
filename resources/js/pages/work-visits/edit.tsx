import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import workVisits from '@/actions/App/Http/Controllers/WorkVisitController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
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
import { formatRupiah } from '@/lib/format';

type IdOption = { id: number; label: string };
type Option = { value: string; label: string };

type WorkVisit = {
    id: number;
    employee_id: number;
    destination: string;
    purpose: string;
    start_date: string;
    end_date: string;
    transport_mode: string | null;
    estimated_cost: number | null;
    notes: string | null;
};

type EditProps = {
    workVisit: WorkVisit;
    employees: IdOption[];
    transportModes: Option[];
};

type WorkVisitForm = {
    employee_id: string;
    destination: string;
    purpose: string;
    start_date: string;
    end_date: string;
    transport_mode: string;
    estimated_cost: string;
    notes: string;
};

const NONE = '__none__';

export default function WorkVisitsEdit({
    workVisit,
    employees,
    transportModes,
}: EditProps) {
    useFlashToast();

    const form = useForm<WorkVisitForm>({
        employee_id: String(workVisit.employee_id),
        destination: workVisit.destination,
        purpose: workVisit.purpose,
        start_date: workVisit.start_date,
        end_date: workVisit.end_date,
        transport_mode: workVisit.transport_mode ?? '',
        estimated_cost:
            workVisit.estimated_cost !== null
                ? String(workVisit.estimated_cost)
                : '',
        notes: workVisit.notes ?? '',
    });

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            transport_mode: data.transport_mode || null,
            estimated_cost: data.estimated_cost
                ? Number(data.estimated_cost)
                : null,
            notes: data.notes || null,
        }));

        form.put(workVisits.update.url(workVisit.id));
    }

    return (
        <>
            <Head title="Edit Kunjungan Kerja" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Edit Kunjungan Kerja"
                    description="Perbarui detail pengajuan kunjungan kerja."
                />

                <form onSubmit={handleSubmit} className="mx-auto w-full max-w-4xl">
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="employee_id">
                                        Karyawan <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.employee_id}
                                        onValueChange={(value) =>
                                            form.setData('employee_id', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="employee_id"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Pilih karyawan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {employees.map((option) => (
                                                <SelectItem
                                                    key={option.id}
                                                    value={String(option.id)}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.employee_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="destination">
                                        Tujuan <RequiredMark />
                                    </Label>
                                    <Input
                                        id="destination"
                                        value={form.data.destination}
                                        onChange={(e) =>
                                            form.setData(
                                                'destination',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Mis. Surabaya — Kantor Cabang"
                                    />
                                    <InputError
                                        message={form.errors.destination}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="start_date">
                                        Tanggal Mulai <RequiredMark />
                                    </Label>
                                    <Input
                                        id="start_date"
                                        type="date"
                                        value={form.data.start_date}
                                        onChange={(e) =>
                                            form.setData(
                                                'start_date',
                                                e.target.value,
                                            )
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
                                    <Input
                                        id="end_date"
                                        type="date"
                                        value={form.data.end_date}
                                        onChange={(e) =>
                                            form.setData(
                                                'end_date',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Pilih tanggal"
                                    />
                                    <InputError message={form.errors.end_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="transport_mode">
                                        Transportasi
                                    </Label>
                                    <Select
                                        value={form.data.transport_mode || NONE}
                                        onValueChange={(value) =>
                                            form.setData(
                                                'transport_mode',
                                                value === NONE ? '' : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="transport_mode"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="— pilih —" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>
                                                — pilih —
                                            </SelectItem>
                                            {transportModes.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.transport_mode}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="estimated_cost">
                                        Estimasi Biaya (Rp)
                                    </Label>
                                    <Input
                                        id="estimated_cost"
                                        type="number"
                                        min={0}
                                        value={form.data.estimated_cost}
                                        onChange={(e) =>
                                            form.setData(
                                                'estimated_cost',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Mis. 1500000"
                                    />
                                    <InputError
                                        message={form.errors.estimated_cost}
                                    />
                                    {form.data.estimated_cost &&
                                        Number(form.data.estimated_cost) > 0 && (
                                            <p className="text-xs text-muted-foreground">
                                                {formatRupiah(
                                                    Number(
                                                        form.data
                                                            .estimated_cost,
                                                    ),
                                                )}
                                            </p>
                                        )}
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="purpose">
                                        Keperluan <RequiredMark />
                                    </Label>
                                    <textarea
                                        id="purpose"
                                        value={form.data.purpose}
                                        onChange={(e) =>
                                            form.setData(
                                                'purpose',
                                                e.target.value,
                                            )
                                        }
                                        rows={3}
                                        placeholder="Jelaskan keperluan kunjungan kerja"
                                        className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <InputError message={form.errors.purpose} />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="notes">Catatan</Label>
                                    <textarea
                                        id="notes"
                                        value={form.data.notes}
                                        onChange={(e) =>
                                            form.setData('notes', e.target.value)
                                        }
                                        rows={3}
                                        placeholder="Catatan tambahan (opsional)"
                                        className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <InputError message={form.errors.notes} />
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="outline" type="button">
                                <Link href={workVisits.show.url(workVisit.id)}>
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
