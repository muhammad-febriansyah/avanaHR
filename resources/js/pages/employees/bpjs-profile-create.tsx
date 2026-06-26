import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import taxBpjs from '@/actions/App/Http/Controllers/EmployeeTaxBpjsController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RupiahInput } from '@/components/ui/rupiah-input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatRupiah } from '@/lib/format';

type Option = { value: string; label: string };

type Props = {
    employee: { id: number; name: string; employee_no: string };
    participationOptions: Option[];
};

type BpjsProfileForm = {
    effective_date: string;
    bpjs_kesehatan_no: string;
    bpjs_tk_no: string;
    kesehatan_basis: string;
    tk_basis: string;
    kesehatan: boolean;
    jht: boolean;
    jkk: boolean;
    jkm: boolean;
    jp: boolean;
};

const emptyForm: BpjsProfileForm = {
    effective_date: '',
    bpjs_kesehatan_no: '',
    bpjs_tk_no: '',
    kesehatan_basis: '',
    tk_basis: '',
    kesehatan: true,
    jht: true,
    jkk: true,
    jkm: true,
    jp: true,
};

export default function BpjsProfileCreate({
    employee,
    participationOptions,
}: Props) {
    useFlashToast();

    const form = useForm<BpjsProfileForm>(emptyForm);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            bpjs_kesehatan_no: data.bpjs_kesehatan_no || null,
            bpjs_tk_no: data.bpjs_tk_no || null,
            kesehatan_basis: data.kesehatan_basis
                ? Number(data.kesehatan_basis)
                : null,
            tk_basis: data.tk_basis ? Number(data.tk_basis) : null,
        }));

        form.post(taxBpjs.storeBpjs.url(employee.id));
    }

    return (
        <>
            <Head title={`Tambah Profil BPJS — ${employee.name}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Profil BPJS"
                    description={`Profil BPJS untuk ${employee.name} (per tanggal efektif).`}
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
                                    <Label htmlFor="bpjs_kesehatan_no">
                                        No. Kesehatan
                                    </Label>
                                    <Input
                                        id="bpjs_kesehatan_no"
                                        value={form.data.bpjs_kesehatan_no}
                                        onChange={(e) =>
                                            form.setData(
                                                'bpjs_kesehatan_no',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Nomor BPJS Kesehatan (opsional)"
                                    />
                                    <InputError
                                        message={form.errors.bpjs_kesehatan_no}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="bpjs_tk_no">
                                        No. Ketenagakerjaan
                                    </Label>
                                    <Input
                                        id="bpjs_tk_no"
                                        value={form.data.bpjs_tk_no}
                                        onChange={(e) =>
                                            form.setData(
                                                'bpjs_tk_no',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Nomor BPJS TK (opsional)"
                                    />
                                    <InputError
                                        message={form.errors.bpjs_tk_no}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="kesehatan_basis">
                                        Basis Kesehatan (Rp)
                                    </Label>
                                    <RupiahInput
                                        id="kesehatan_basis"
                                        value={form.data.kesehatan_basis}
                                        onChange={(value) =>
                                            form.setData(
                                                'kesehatan_basis',
                                                value,
                                            )
                                        }
                                        placeholder="Mis. 5000000"
                                    />
                                    <InputError
                                        message={form.errors.kesehatan_basis}
                                    />
                                    {form.data.kesehatan_basis &&
                                        Number(form.data.kesehatan_basis) >
                                            0 && (
                                            <p className="text-xs text-muted-foreground">
                                                {formatRupiah(
                                                    Number(
                                                        form.data
                                                            .kesehatan_basis,
                                                    ),
                                                )}
                                            </p>
                                        )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="tk_basis">
                                        Basis TK (Rp)
                                    </Label>
                                    <RupiahInput
                                        id="tk_basis"
                                        value={form.data.tk_basis}
                                        onChange={(value) =>
                                            form.setData('tk_basis', value)
                                        }
                                        placeholder="Mis. 5000000"
                                    />
                                    <InputError
                                        message={form.errors.tk_basis}
                                    />
                                    {form.data.tk_basis &&
                                        Number(form.data.tk_basis) > 0 && (
                                            <p className="text-xs text-muted-foreground">
                                                {formatRupiah(
                                                    Number(form.data.tk_basis),
                                                )}
                                            </p>
                                        )}
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label>Keikutsertaan</Label>
                                    <div className="flex flex-wrap gap-4">
                                        {participationOptions.map((option) => (
                                            <label
                                                key={option.value}
                                                className="flex items-center gap-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={
                                                        form.data[
                                                            option.value as keyof BpjsProfileForm
                                                        ] as boolean
                                                    }
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        form.setData(
                                                            option.value as keyof BpjsProfileForm,
                                                            Boolean(checked),
                                                        )
                                                    }
                                                />
                                                {option.label}
                                            </label>
                                        ))}
                                    </div>
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
