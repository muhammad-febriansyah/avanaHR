import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import workVisits from '@/actions/App/Http/Controllers/WorkVisitController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RupiahInput } from '@/components/ui/rupiah-input';
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

type CreateProps = {
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

const emptyForm: WorkVisitForm = {
    employee_id: '',
    destination: '',
    purpose: '',
    start_date: '',
    end_date: '',
    transport_mode: '',
    estimated_cost: '',
    notes: '',
};

export default function WorkVisitsCreate({
    employees,
    transportModes,
}: CreateProps) {
    useFlashToast();

    const form = useForm<WorkVisitForm>(emptyForm);

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

        form.post(workVisits.store.url());
    }

    return (
        <>
            <Head title="Ajukan Kunjungan Kerja" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Ajukan Kunjungan Kerja"
                    description="Catat pengajuan kunjungan kerja / perjalanan dinas karyawan."
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
                                    <RupiahInput
                                        id="estimated_cost"
                                        value={form.data.estimated_cost}
                                        onChange={(value) =>
                                            form.setData(
                                                'estimated_cost',
                                                value,
                                            )
                                        }
                                        placeholder="Mis. 1500000"
                                    />
                                    <InputError
                                        message={form.errors.estimated_cost}
                                    />
                                    {form.data.estimated_cost &&
                                        Number(form.data.estimated_cost) >
                                            0 && (
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
                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                    />
                                    <InputError message={form.errors.purpose} />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="notes">Catatan</Label>
                                    <textarea
                                        id="notes"
                                        value={form.data.notes}
                                        onChange={(e) =>
                                            form.setData(
                                                'notes',
                                                e.target.value,
                                            )
                                        }
                                        rows={3}
                                        placeholder="Catatan tambahan (opsional)"
                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                    />
                                    <InputError message={form.errors.notes} />
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="secondary" type="button">
                                <Link href={workVisits.index.url()}>
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
