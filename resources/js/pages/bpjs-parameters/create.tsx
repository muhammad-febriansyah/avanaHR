import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import bpjsParameters from '@/actions/App/Http/Controllers/BpjsParameterController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFlashToast } from '@/hooks/use-flash-toast';

type TkRates = {
    jht_employee?: number;
    jht_employer?: number;
    jkk?: number;
    jkm?: number;
    jp_employee?: number;
    jp_employer?: number;
    jp_cap?: number;
};

type Defaults = {
    kes_rate_employee: number;
    kes_rate_employer: number;
    kes_cap: number;
    tk_rates: TkRates;
};

type CreateProps = { defaults: Defaults };

const RATE_FIELDS = [
    { key: 'kes_rate_employee', label: 'Kesehatan Karyawan (%)' },
    { key: 'kes_rate_employer', label: 'Kesehatan Perusahaan (%)' },
    { key: 'jht_employee', label: 'JHT Karyawan (%)' },
    { key: 'jht_employer', label: 'JHT Perusahaan (%)' },
    { key: 'jkk', label: 'JKK (%)' },
    { key: 'jkm', label: 'JKM (%)' },
    { key: 'jp_employee', label: 'JP Karyawan (%)' },
    { key: 'jp_employer', label: 'JP Perusahaan (%)' },
];

const CAP_FIELDS = [
    { key: 'kes_cap', label: 'Plafon Kesehatan (Rp)' },
    { key: 'jp_cap', label: 'Plafon JP (Rp)' },
];

export default function BpjsParametersCreate({ defaults }: CreateProps) {
    useFlashToast();

    const form = useForm<Record<string, string>>({
        effective_date: '',
        kes_rate_employee: String(defaults.kes_rate_employee),
        kes_rate_employer: String(defaults.kes_rate_employer),
        kes_cap: String(defaults.kes_cap),
        jht_employee: String(defaults.tk_rates.jht_employee ?? 0),
        jht_employer: String(defaults.tk_rates.jht_employer ?? 0),
        jkk: String(defaults.tk_rates.jkk ?? 0),
        jkm: String(defaults.tk_rates.jkm ?? 0),
        jp_employee: String(defaults.tk_rates.jp_employee ?? 0),
        jp_employer: String(defaults.tk_rates.jp_employer ?? 0),
        jp_cap: String(defaults.tk_rates.jp_cap ?? 0),
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(bpjsParameters.store.url());
    }

    return (
        <>
            <Head title="Tambah Parameter BPJS" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Parameter BPJS"
                    description="Iuran & plafon BPJS yang berlaku mulai tanggal efektif. Prefill dari nilai default."
                >
                    <Button asChild variant="outline">
                        <Link href={bpjsParameters.index.url()}>
                            <X />
                            Batal
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Tanggal Berlaku
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid max-w-xs gap-2">
                                <Label>
                                    Tanggal Efektif <RequiredMark />
                                </Label>
                                <DatePicker
                                    value={form.data.effective_date}
                                    onChange={(value) =>
                                        form.setData('effective_date', value)
                                    }
                                    placeholder="Pilih tanggal"
                                />
                                <InputError
                                    message={form.errors.effective_date}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Tarif Iuran (%)
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            {RATE_FIELDS.map((field) => (
                                <div key={field.key} className="grid gap-2">
                                    <Label htmlFor={field.key}>
                                        {field.label}
                                    </Label>
                                    <Input
                                        id={field.key}
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={form.data[field.key]}
                                        onChange={(e) =>
                                            form.setData(
                                                field.key,
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Mis. 1"
                                    />
                                    <InputError
                                        message={form.errors[field.key]}
                                    />
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Plafon Upah (Rp)
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            {CAP_FIELDS.map((field) => (
                                <div key={field.key} className="grid gap-2">
                                    <Label htmlFor={field.key}>
                                        {field.label}
                                    </Label>
                                    <Input
                                        id={field.key}
                                        type="number"
                                        min="0"
                                        value={form.data[field.key]}
                                        onChange={(e) =>
                                            form.setData(
                                                field.key,
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Mis. 12000000"
                                    />
                                    <InputError
                                        message={form.errors[field.key]}
                                    />
                                </div>
                            ))}
                        </CardContent>
                        <CardFooter className="justify-end">
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
