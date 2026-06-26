import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import reimbursements from '@/actions/App/Http/Controllers/ReimbursementController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
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

type Option = { value: string; label: string };
type EmployeeOption = { id: number; label: string };

type CreateProps = {
    categories: Option[];
    settlements: Option[];
    employees: EmployeeOption[];
};

export default function ReimbursementCreate({
    categories,
    settlements,
    employees,
}: CreateProps) {
    useFlashToast();

    const form = useForm({
        employee_id: '',
        category: 'medical',
        amount: '',
        settlement: 'payroll',
    });

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({ ...data, amount: Number(data.amount) }));
        form.post(reimbursements.store.url());
    }

    return (
        <>
            <Head title="Ajukan Reimbursement" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Ajukan Reimbursement"
                    description="Catat klaim reimbursement karyawan. Persetujuan diproses lewat Inbox Approval."
                >
                    <Button asChild variant="outline">
                        <Link href={reimbursements.index.url()}>
                            <X />
                            Batal
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardContent className="grid gap-5 md:grid-cols-2">
                            <div className="grid gap-2">
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
                                <Label htmlFor="category">
                                    Kategori <RequiredMark />
                                </Label>
                                <Select
                                    value={form.data.category}
                                    onValueChange={(value) =>
                                        form.setData('category', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="category"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.category} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="amount">
                                    Nominal <RequiredMark />
                                </Label>
                                <RupiahInput
                                    id="amount"
                                    value={form.data.amount}
                                    onChange={(value) =>
                                        form.setData('amount', value)
                                    }
                                    placeholder="Mis. 500000"
                                />
                                <InputError message={form.errors.amount} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="settlement">
                                    Metode Bayar <RequiredMark />
                                </Label>
                                <Select
                                    value={form.data.settlement}
                                    onValueChange={(value) =>
                                        form.setData('settlement', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="settlement"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih metode" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {settlements.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.settlement} />
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
