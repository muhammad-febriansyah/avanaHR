import { Form, Link } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import { useState } from 'react';
import employees from '@/actions/App/Http/Controllers/EmployeeController';
import InputError from '@/components/input-error';
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
import type { EmployeeFull, StatusOption } from '@/types/employee';

type EmployeeFormProps = {
    statuses: StatusOption[];
    employee?: EmployeeFull;
};

const GENDER_OPTIONS: StatusOption[] = [
    { value: 'male', label: 'Laki-laki' },
    { value: 'female', label: 'Perempuan' },
];

/**
 * Shared create/edit form for an employee.
 */
export default function EmployeeForm({
    statuses,
    employee,
}: EmployeeFormProps) {
    const formProps = employee
        ? employees.update.form(employee.id)
        : employees.store.form();

    const [gender, setGender] = useState(employee?.gender ?? '');
    const [status, setStatus] = useState(employee?.status ?? '');

    return (
        <Form {...formProps} className="mx-auto w-full max-w-4xl">
            {({ processing, errors }) => (
                <Card>
                    <CardContent className="grid gap-5 md:grid-cols-2">
                        <input type="hidden" name="gender" value={gender} />
                        <input type="hidden" name="status" value={status} />

                        <div className="grid gap-2">
                            <Label htmlFor="employee_no">
                                No. Karyawan <RequiredMark />
                            </Label>
                            <Input
                                id="employee_no"
                                name="employee_no"
                                defaultValue={employee?.employee_no ?? ''}
                                required
                                placeholder="EMP-0001"
                            />
                            <InputError message={errors.employee_no} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="first_name">
                                Nama Depan <RequiredMark />
                            </Label>
                            <Input
                                id="first_name"
                                name="first_name"
                                defaultValue={employee?.first_name ?? ''}
                                required
                                placeholder="Budi"
                            />
                            <InputError message={errors.first_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="last_name">Nama Belakang</Label>
                            <Input
                                id="last_name"
                                name="last_name"
                                defaultValue={employee?.last_name ?? ''}
                                placeholder="Santoso"
                            />
                            <InputError message={errors.last_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="status">
                                Status <RequiredMark />
                            </Label>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger id="status" className="w-full">
                                    <SelectValue placeholder="Pilih status" />
                                </SelectTrigger>
                                <SelectContent>
                                    {statuses.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="gender">Jenis Kelamin</Label>
                            <Select value={gender} onValueChange={setGender}>
                                <SelectTrigger id="gender" className="w-full">
                                    <SelectValue placeholder="Pilih jenis kelamin" />
                                </SelectTrigger>
                                <SelectContent>
                                    {GENDER_OPTIONS.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.gender} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="birth_place">Tempat Lahir</Label>
                            <Input
                                id="birth_place"
                                name="birth_place"
                                defaultValue={employee?.birth_place ?? ''}
                                placeholder="Jakarta"
                            />
                            <InputError message={errors.birth_place} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="birth_date">Tanggal Lahir</Label>
                            <Input
                                id="birth_date"
                                name="birth_date"
                                type="date"
                                defaultValue={employee?.birth_date ?? ''}
                            />
                            <InputError message={errors.birth_date} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="join_date">Tanggal Bergabung</Label>
                            <Input
                                id="join_date"
                                name="join_date"
                                type="date"
                                defaultValue={employee?.join_date ?? ''}
                            />
                            <InputError message={errors.join_date} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="religion">Agama</Label>
                            <Input
                                id="religion"
                                name="religion"
                                defaultValue={employee?.religion ?? ''}
                                placeholder="Islam"
                            />
                            <InputError message={errors.religion} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="marital_status">
                                Status Pernikahan
                            </Label>
                            <Input
                                id="marital_status"
                                name="marital_status"
                                defaultValue={employee?.marital_status ?? ''}
                                placeholder="Menikah"
                            />
                            <InputError message={errors.marital_status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="nik_ktp">NIK KTP</Label>
                            <Input
                                id="nik_ktp"
                                name="nik_ktp"
                                defaultValue={employee?.nik_ktp ?? ''}
                                placeholder="3171xxxxxxxxxxxx"
                            />
                            <InputError message={errors.nik_ktp} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="npwp">NPWP</Label>
                            <Input
                                id="npwp"
                                name="npwp"
                                defaultValue={employee?.npwp ?? ''}
                                placeholder="00.000.000.0-000.000"
                            />
                            <InputError message={errors.npwp} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                defaultValue={employee?.email ?? ''}
                                placeholder="budi@perusahaan.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Telepon</Label>
                            <Input
                                id="phone"
                                name="phone"
                                defaultValue={employee?.phone ?? ''}
                                placeholder="0812xxxxxxxx"
                            />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="grid gap-2 md:col-span-2">
                            <Label htmlFor="address">Alamat</Label>
                            <textarea
                                id="address"
                                name="address"
                                defaultValue={employee?.address ?? ''}
                                rows={3}
                                placeholder="Alamat lengkap"
                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError message={errors.address} />
                        </div>
                    </CardContent>
                    <CardFooter className="flex justify-end gap-3">
                        <Button asChild variant="outline" type="button">
                            <Link href={employees.index.url()}>
                                <X />
                                Batal
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save />
                            Simpan
                        </Button>
                    </CardFooter>
                </Card>
            )}
        </Form>
    );
}
