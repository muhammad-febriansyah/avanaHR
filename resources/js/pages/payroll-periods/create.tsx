import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import payrollPeriods from '@/actions/App/Http/Controllers/PayrollPeriodController';
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

type PeriodForm = {
    code: string;
    month: string;
    year: string;
    cutoff_date: string;
    pay_date: string;
};

const MONTHS = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const emptyForm: PeriodForm = {
    code: '',
    month: '1',
    year: '2026',
    cutoff_date: '',
    pay_date: '',
};

export default function PayrollPeriodsCreate() {
    useFlashToast();

    const form = useForm<PeriodForm>(emptyForm);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            month: Number(data.month),
            year: Number(data.year),
            cutoff_date: data.cutoff_date || null,
            pay_date: data.pay_date || null,
        }));

        form.post(payrollPeriods.store.url());
    }

    return (
        <>
            <Head title="Tambah Periode Payroll" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Periode Payroll"
                    description="Buat periode penggajian bulanan baru."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="code">
                                        Kode <RequiredMark />
                                    </Label>
                                    <Input
                                        id="code"
                                        value={form.data.code}
                                        onChange={(e) =>
                                            form.setData('code', e.target.value)
                                        }
                                        placeholder="Mis. PR-2026-07"
                                        autoFocus
                                    />
                                    <InputError message={form.errors.code} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="month">
                                        Bulan <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.month}
                                        onValueChange={(value) =>
                                            form.setData('month', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="month"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {MONTHS.map((label, index) => (
                                                <SelectItem
                                                    key={label}
                                                    value={String(index + 1)}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.month} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="year">
                                        Tahun <RequiredMark />
                                    </Label>
                                    <Input
                                        id="year"
                                        type="number"
                                        min={2000}
                                        max={2100}
                                        value={form.data.year}
                                        onChange={(e) =>
                                            form.setData('year', e.target.value)
                                        }
                                        placeholder="Mis. 2026"
                                    />
                                    <InputError message={form.errors.year} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="cutoff_date">
                                        Tanggal Cut-off
                                    </Label>
                                    <DatePicker
                                        id="cutoff_date"
                                        value={form.data.cutoff_date}
                                        onChange={(value) =>
                                            form.setData('cutoff_date', value)
                                        }
                                        placeholder="Pilih tanggal"
                                    />
                                    <InputError
                                        message={form.errors.cutoff_date}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="pay_date">
                                        Tanggal Bayar
                                    </Label>
                                    <DatePicker
                                        id="pay_date"
                                        value={form.data.pay_date}
                                        onChange={(value) =>
                                            form.setData('pay_date', value)
                                        }
                                        placeholder="Pilih tanggal"
                                    />
                                    <InputError
                                        message={form.errors.pay_date}
                                    />
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="secondary" type="button">
                                <Link href={payrollPeriods.index.url()}>
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
