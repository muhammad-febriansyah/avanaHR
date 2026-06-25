import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import employeeBenefits from '@/actions/App/Http/Controllers/EmployeeBenefitController';
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

type EmployeeOption = { id: number; label: string };
type BenefitTypeOption = {
    id: number;
    label: string;
    default_quota: number | null;
};

type CreateProps = {
    employees: EmployeeOption[];
    benefitTypes: BenefitTypeOption[];
    currentYear: number;
};

type BenefitForm = {
    employee_id: string;
    benefit_type_id: string;
    period_year: string;
    quota: string;
    notes: string;
};

export default function EmployeeBenefitsCreate({
    employees,
    benefitTypes,
    currentYear,
}: CreateProps) {
    useFlashToast();

    const form = useForm<BenefitForm>({
        employee_id: '',
        benefit_type_id: '',
        period_year: String(currentYear),
        quota: '',
        notes: '',
    });

    function handleBenefitTypeChange(value: string) {
        form.setData('benefit_type_id', value);

        const selected = benefitTypes.find(
            (type) => String(type.id) === value,
        );

        if (
            selected &&
            selected.default_quota !== null &&
            form.data.quota === ''
        ) {
            form.setData('quota', String(selected.default_quota));
        }
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            employee_id: data.employee_id ? Number(data.employee_id) : null,
            benefit_type_id: data.benefit_type_id
                ? Number(data.benefit_type_id)
                : null,
            period_year: data.period_year ? Number(data.period_year) : null,
            quota: data.quota ? Number(data.quota) : null,
            notes: data.notes || null,
        }));

        form.post(employeeBenefits.store.url());
    }

    return (
        <>
            <Head title="Tetapkan Benefit Karyawan" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tetapkan Benefit Karyawan"
                    description="Tetapkan plafon benefit untuk karyawan pada periode tertentu."
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
                                    <Label htmlFor="benefit_type_id">
                                        Jenis Benefit <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.benefit_type_id}
                                        onValueChange={handleBenefitTypeChange}
                                    >
                                        <SelectTrigger
                                            id="benefit_type_id"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Pilih jenis benefit" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {benefitTypes.map((option) => (
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
                                        message={form.errors.benefit_type_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="period_year">
                                        Tahun Periode <RequiredMark />
                                    </Label>
                                    <Input
                                        id="period_year"
                                        type="number"
                                        value={form.data.period_year}
                                        onChange={(e) =>
                                            form.setData(
                                                'period_year',
                                                e.target.value,
                                            )
                                        }
                                        placeholder={String(currentYear)}
                                    />
                                    <InputError
                                        message={form.errors.period_year}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="quota">
                                        Plafon (Rp) <RequiredMark />
                                    </Label>
                                    <Input
                                        id="quota"
                                        type="number"
                                        min={0}
                                        value={form.data.quota}
                                        onChange={(e) =>
                                            form.setData('quota', e.target.value)
                                        }
                                        placeholder="Mis. 5000000"
                                    />
                                    {form.data.quota &&
                                        Number(form.data.quota) > 0 && (
                                            <p className="text-xs text-muted-foreground">
                                                {formatRupiah(
                                                    Number(form.data.quota),
                                                )}
                                            </p>
                                        )}
                                    <InputError message={form.errors.quota} />
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
                                <Link href={employeeBenefits.index.url()}>
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
