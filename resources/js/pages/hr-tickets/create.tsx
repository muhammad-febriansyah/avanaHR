import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import hrTickets from '@/actions/App/Http/Controllers/HrTicketController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
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

type IdOption = { id: number; label: string };
type Option = { value: string; label: string };

type CreateProps = {
    categories: Option[];
    priorities: Option[];
    employees: IdOption[];
};

type TicketForm = {
    subject: string;
    category: string;
    priority: string;
    employee_id: string;
    body: string;
};

const NONE = '__none__';

const emptyForm: TicketForm = {
    subject: '',
    category: 'general',
    priority: 'medium',
    employee_id: '',
    body: '',
};

export default function HrTicketsCreate({
    categories,
    priorities,
    employees,
}: CreateProps) {
    useFlashToast();

    const form = useForm<TicketForm>(emptyForm);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            employee_id: data.employee_id || null,
        }));

        form.post(hrTickets.store.url());
    }

    return (
        <>
            <Head title="Buat Tiket HR" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Buat Tiket HR"
                    description="Ajukan pertanyaan atau permintaan karyawan ke tim HR."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="subject">
                                        Subjek <RequiredMark />
                                    </Label>
                                    <Input
                                        id="subject"
                                        value={form.data.subject}
                                        onChange={(e) =>
                                            form.setData(
                                                'subject',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Mis. Koreksi data NPWP"
                                    />
                                    <InputError message={form.errors.subject} />
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
                                    <InputError
                                        message={form.errors.category}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="priority">
                                        Prioritas <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.priority}
                                        onValueChange={(value) =>
                                            form.setData('priority', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="priority"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Pilih prioritas" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {priorities.map((option) => (
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
                                        message={form.errors.priority}
                                    />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="employee_id">
                                        Karyawan (opsional)
                                    </Label>
                                    <Combobox
                                        id="employee_id"
                                        value={form.data.employee_id || NONE}
                                        onChange={(value) =>
                                            form.setData(
                                                'employee_id',
                                                value === NONE ? '' : value,
                                            )
                                        }
                                        options={[
                                            {
                                                value: NONE,
                                                label: '— tidak ada —',
                                            },
                                            ...employees.map((option) => ({
                                                value: String(option.id),
                                                label: option.label,
                                            })),
                                        ]}
                                        placeholder="Pilih karyawan"
                                        searchPlaceholder="Cari karyawan…"
                                        emptyText="Karyawan tidak ditemukan"
                                    />
                                    <InputError
                                        message={form.errors.employee_id}
                                    />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="body">
                                        Deskripsi <RequiredMark />
                                    </Label>
                                    <textarea
                                        id="body"
                                        value={form.data.body}
                                        onChange={(e) =>
                                            form.setData('body', e.target.value)
                                        }
                                        rows={5}
                                        placeholder="Jelaskan detail permintaan…"
                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                    />
                                    <InputError message={form.errors.body} />
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="secondary" type="button">
                                <Link href={hrTickets.index.url()}>
                                    <X />
                                    Batal
                                </Link>
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                <Save />
                                Kirim
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </>
    );
}
