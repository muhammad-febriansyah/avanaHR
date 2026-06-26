import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import employeeLoans from '@/actions/App/Http/Controllers/EmployeeLoanController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RupiahInput } from '@/components/ui/rupiah-input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatRupiah } from '@/lib/format';

type EmployeeOption = { id: number; label: string };

type CreateProps = {
    employees: EmployeeOption[];
};

export default function EmployeeLoanCreate({ employees }: CreateProps) {
    useFlashToast();

    const form = useForm({
        employee_id: '',
        principal: '',
        tenor_months: '',
    });

    const principal = Number(form.data.principal) || 0;
    const tenor = Number(form.data.tenor_months) || 0;
    const installment = tenor > 0 ? Math.ceil(principal / tenor) : 0;

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            principal: Number(data.principal),
            tenor_months: Number(data.tenor_months),
        }));
        form.post(employeeLoans.store.url());
    }

    return (
        <>
            <Head title="Ajukan Pinjaman" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Ajukan Pinjaman"
                    description="Catat pengajuan pinjaman karyawan. Cicilan dipotong otomatis di payroll setelah disetujui."
                >
                    <Button asChild variant="outline">
                        <Link href={employeeLoans.index.url()}>
                            <X />
                            Batal
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardContent className="grid gap-5 md:grid-cols-2">
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="employee_id">
                                    Karyawan <RequiredMark />
                                </Label>
                                <Combobox
                                    options={employees.map((e) => ({
                                        value: String(e.id),
                                        label: e.label,
                                    }))}
                                    value={form.data.employee_id}
                                    onChange={(value) =>
                                        form.setData('employee_id', value)
                                    }
                                    placeholder="Pilih karyawan"
                                />
                                <InputError message={form.errors.employee_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="principal">
                                    Pokok Pinjaman <RequiredMark />
                                </Label>
                                <RupiahInput
                                    id="principal"
                                    value={form.data.principal}
                                    onChange={(value) =>
                                        form.setData('principal', value)
                                    }
                                    placeholder="Mis. 6000000"
                                />
                                <InputError message={form.errors.principal} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tenor_months">
                                    Tenor (bulan) <RequiredMark />
                                </Label>
                                <Input
                                    id="tenor_months"
                                    type="number"
                                    min="1"
                                    max="120"
                                    value={form.data.tenor_months}
                                    onChange={(e) =>
                                        form.setData(
                                            'tenor_months',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Mis. 12"
                                />
                                <InputError
                                    message={form.errors.tenor_months}
                                />
                            </div>

                            <div className="rounded-lg bg-muted p-3 text-sm md:col-span-2">
                                Estimasi cicilan per bulan:{' '}
                                <span className="font-semibold text-navy">
                                    {formatRupiah(installment)}
                                </span>
                            </div>
                        </CardContent>
                        <CardFooter className="justify-end">
                            <Button type="submit" disabled={form.processing}>
                                <Save />
                                Ajukan
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </>
    );
}
