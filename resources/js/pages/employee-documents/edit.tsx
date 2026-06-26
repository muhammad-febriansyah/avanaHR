import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import employeeDocuments from '@/actions/App/Http/Controllers/EmployeeDocumentController';
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

type Option = { value: string; label: string };

type EmployeeDocumentItem = {
    id: number;
    employee_id: number;
    employee_name: string | null;
    employee_no: string | null;
    document_type: string;
    number: string | null;
    issued_at: string | null;
    expired_at: string | null;
    reminder_days: number | null;
    access_level: string;
    file_url: string | null;
};

type EditProps = {
    document: EmployeeDocumentItem;
    types: Option[];
    accessLevels: Option[];
};

type DocForm = {
    document_type: string;
    number: string;
    issued_at: string;
    expired_at: string;
    reminder_days: string;
    access_level: string;
};

export default function EmployeeDocumentsEdit({
    document,
    types,
    accessLevels,
}: EditProps) {
    useFlashToast();

    const form = useForm<DocForm>({
        document_type: document.document_type,
        number: document.number ?? '',
        issued_at: document.issued_at ?? '',
        expired_at: document.expired_at ?? '',
        reminder_days:
            document.reminder_days !== null
                ? String(document.reminder_days)
                : '',
        access_level: document.access_level,
    });

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.put(employeeDocuments.update.url(document.id));
    }

    return (
        <>
            <Head title="Edit Dokumen" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Edit Dokumen"
                    description="Perbarui detail dokumen karyawan."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="rounded-lg border bg-muted/40 p-3 text-sm">
                                <div className="font-medium">
                                    {document.employee_name}
                                </div>
                                <div className="text-muted-foreground">
                                    {document.employee_no}
                                </div>
                            </div>

                            <div className="grid gap-5 md:grid-cols-2">
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
                                    <Label>Berkas Saat Ini</Label>
                                    {document.file_url ? (
                                        <a
                                            href={document.file_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-sm font-medium text-primary underline-offset-2 hover:underline"
                                        >
                                            Unduh berkas
                                        </a>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            Tidak ada berkas terlampir.
                                        </p>
                                    )}
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
