import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import employeeDocuments from '@/actions/App/Http/Controllers/EmployeeDocumentController';
import FileDropzone from '@/components/file-dropzone';
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

type IdOption = { id: number; label: string };
type Option = { value: string; label: string };

type CreateProps = {
    employees: IdOption[];
    types: Option[];
    accessLevels: Option[];
};

type DocForm = {
    employee_id: string;
    document_type: string;
    number: string;
    issued_at: string;
    expired_at: string;
    reminder_days: string;
    access_level: string;
    file: File | null;
};

const emptyForm: DocForm = {
    employee_id: '',
    document_type: 'ktp',
    number: '',
    issued_at: '',
    expired_at: '',
    reminder_days: '30',
    access_level: 'internal',
    file: null,
};

export default function EmployeeDocumentsCreate({
    employees,
    types,
    accessLevels,
}: CreateProps) {
    useFlashToast();

    const form = useForm<DocForm>(emptyForm);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.post(employeeDocuments.store.url(), { forceFormData: true });
    }

    return (
        <>
            <Head title="Tambah Dokumen" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Dokumen"
                    description="Catat dokumen karyawan beserta masa berlaku & pengingat."
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
                                    <Label htmlFor="document_type">
                                        Jenis Dokumen <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.document_type}
                                        onValueChange={(value) =>
                                            form.setData('document_type', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="document_type"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Pilih jenis" />
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
                                    <InputError
                                        message={form.errors.document_type}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="number">Nomor</Label>
                                    <Input
                                        id="number"
                                        value={form.data.number}
                                        onChange={(e) =>
                                            form.setData(
                                                'number',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Nomor dokumen"
                                    />
                                    <InputError message={form.errors.number} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="issued_at">
                                        Tanggal Terbit
                                    </Label>
                                    <DatePicker
                                        id="issued_at"
                                        value={form.data.issued_at}
                                        onChange={(value) =>
                                            form.setData('issued_at', value)
                                        }
                                        placeholder="Pilih tanggal"
                                    />
                                    <InputError
                                        message={form.errors.issued_at}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="expired_at">
                                        Berlaku s.d.
                                    </Label>
                                    <DatePicker
                                        id="expired_at"
                                        value={form.data.expired_at}
                                        onChange={(value) =>
                                            form.setData('expired_at', value)
                                        }
                                        placeholder="Pilih tanggal"
                                    />
                                    <InputError
                                        message={form.errors.expired_at}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="reminder_days">
                                        Ingatkan (hari)
                                    </Label>
                                    <Input
                                        id="reminder_days"
                                        type="number"
                                        min="0"
                                        max="365"
                                        value={form.data.reminder_days}
                                        onChange={(e) =>
                                            form.setData(
                                                'reminder_days',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Mis. 30"
                                    />
                                    <InputError
                                        message={form.errors.reminder_days}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="access_level">
                                        Level Akses <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.access_level}
                                        onValueChange={(value) =>
                                            form.setData('access_level', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="access_level"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Pilih level akses" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {accessLevels.map((option) => (
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
                                        message={form.errors.access_level}
                                    />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="file">
                                        Berkas (PDF / Gambar)
                                    </Label>
                                    <FileDropzone
                                        id="file"
                                        value={form.data.file}
                                        onChange={(file) =>
                                            form.setData('file', file)
                                        }
                                        accept=".pdf,.png,.jpg,.jpeg,.webp"
                                        hint="PDF/PNG/JPG/WEBP · maks 4 MB"
                                        variant="file"
                                    />
                                    <InputError message={form.errors.file} />
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="secondary" type="button">
                                <Link href={employeeDocuments.index.url()}>
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
