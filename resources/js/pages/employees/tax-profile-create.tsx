import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import taxBpjs from '@/actions/App/Http/Controllers/EmployeeTaxBpjsController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
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

type Option = { value: string; label: string };

type Props = {
    employee: { id: number; name: string; employee_no: string };
    ptkpOptions: string[];
    taxMethodOptions: Option[];
};

type TaxProfileForm = {
    effective_date: string;
    ptkp_status: string;
    npwp: string;
    tax_method: string;
    beginning_ytd: string;
};

const emptyForm: TaxProfileForm = {
    effective_date: '',
    ptkp_status: 'TK/0',
    npwp: '',
    tax_method: 'ter',
    beginning_ytd: '0',
};

export default function TaxProfileCreate({
    employee,
    ptkpOptions,
    taxMethodOptions,
}: Props) {
    useFlashToast();

    const form = useForm<TaxProfileForm>(emptyForm);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            npwp: data.npwp || null,
            beginning_ytd: data.beginning_ytd
                ? Number(data.beginning_ytd)
                : null,
        }));

        form.post(taxBpjs.storeTax.url(employee.id));
    }

    return (
        <>
            <Head title={`Tambah Profil Pajak — ${employee.name}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Profil Pajak"
                    description={`Profil PTKP/pajak untuk ${employee.name} (per tanggal efektif).`}
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="effective_date">
                                        Tanggal Berlaku <RequiredMark />
                                    </Label>
                                    <DatePicker
                                        id="effective_date"
                                        value={form.data.effective_date}
                                        onChange={(value) =>
                                            form.setData(
                                                'effective_date',
                                                value,
                                            )
                                        }
                                        placeholder="Pilih tanggal"
                                    />
                                    <InputError
                                        message={form.errors.effective_date}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="ptkp_status">
                                        Status PTKP <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.ptkp_status}
                                        onValueChange={(value) =>
                                            form.setData('ptkp_status', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="ptkp_status"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Pilih status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ptkpOptions.map((option) => (
                                                <SelectItem
                                                    key={option}
                                                    value={option}
                                                >
                                                    {option}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.ptkp_status}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="tax_method">
                                        Metode <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.tax_method}
                                        onValueChange={(value) =>
                                            form.setData('tax_method', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="tax_method"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Pilih metode" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {taxMethodOptions.map((option) => (
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
                                        message={form.errors.tax_method}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="npwp">NPWP</Label>
                                    <Input
                                        id="npwp"
                                        value={form.data.npwp}
                                        onChange={(e) =>
                                            form.setData('npwp', e.target.value)
                                        }
                                        placeholder="Nomor NPWP (opsional)"
                                    />
                                    <InputError message={form.errors.npwp} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="beginning_ytd">
                                        YTD Awal (Rp)
                                    </Label>
                                    <RupiahInput
                                        id="beginning_ytd"
                                        value={form.data.beginning_ytd}
                                        onChange={(value) =>
                                            form.setData('beginning_ytd', value)
                                        }
                                        placeholder="Mis. 0"
                                    />
                                    <InputError
                                        message={form.errors.beginning_ytd}
                                    />
                                    {form.data.beginning_ytd &&
                                        Number(form.data.beginning_ytd) > 0 && (
                                            <p className="text-xs text-muted-foreground">
                                                {formatRupiah(
                                                    Number(
                                                        form.data.beginning_ytd,
                                                    ),
                                                )}
                                            </p>
                                        )}
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="secondary" type="button">
                                <Link href={taxBpjs.index.url(employee.id)}>
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
