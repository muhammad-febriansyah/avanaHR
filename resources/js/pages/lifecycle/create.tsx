import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import lifecycle from '@/actions/App/Http/Controllers/EmployeeLifecycleEventController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
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

type Option = { value: string; label: string };
type EmployeeOption = { id: number; label: string };

type CreateProps = {
    types: Option[];
    options: { employees: EmployeeOption[] };
};

type EventForm = {
    employee_id: string;
    type: string;
    effective_date: string;
    from_value: string;
    to_value: string;
    reason: string;
};

const emptyForm: EventForm = {
    employee_id: '',
    type: 'promotion',
    effective_date: '',
    from_value: '',
    to_value: '',
    reason: '',
};

export default function LifecycleCreate({ types, options }: CreateProps) {
    useFlashToast();

    const form = useForm<EventForm>(emptyForm);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.post(lifecycle.store.url());
    }

    return (
        <>
            <Head title="Catat Peristiwa Lifecycle" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Catat Peristiwa Lifecycle"
                    description="Catat peristiwa karier karyawan: bergabung, promosi, mutasi, hingga keluar."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="employee_id">
                                        Karyawan <RequiredMark />
                                    </Label>
                                    <Combobox
                                        id="employee_id"
                                        value={form.data.employee_id}
                                        onChange={(value) =>
                                            form.setData('employee_id', value)
                                        }
                                        options={options.employees.map(
                                            (option) => ({
                                                value: String(option.id),
                                                label: option.label,
                                            }),
                                        )}
                                        placeholder="Pilih karyawan"
                                        searchPlaceholder="Cari karyawan…"
                                        emptyText="Karyawan tidak ditemukan"
                                    />
                                    <InputError
                                        message={form.errors.employee_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="type">
                                        Jenis Peristiwa <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.type}
                                        onValueChange={(value) =>
                                            form.setData('type', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="type"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {types.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="effective_date">
                                        Tanggal Efektif <RequiredMark />
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
                                    <Label htmlFor="from_value">
                                        Dari (opsional)
                                    </Label>
                                    <Input
                                        id="from_value"
                                        value={form.data.from_value}
                                        onChange={(e) =>
                                            form.setData(
                                                'from_value',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Mis. Staff"
                                    />
                                    <InputError
                                        message={form.errors.from_value}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="to_value">
                                        Menjadi (opsional)
                                    </Label>
                                    <Input
                                        id="to_value"
                                        value={form.data.to_value}
                                        onChange={(e) =>
                                            form.setData(
                                                'to_value',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Mis. Supervisor"
                                    />
                                    <InputError
                                        message={form.errors.to_value}
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
                                        placeholder="Catatan / dasar keputusan (opsional)"
                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                    />
                                    <InputError message={form.errors.reason} />
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="secondary" type="button">
                                <Link href={lifecycle.index.url()}>
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
