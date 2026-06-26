import { Form, Link } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import { useState } from 'react';
import employees from '@/actions/App/Http/Controllers/EmployeeController';
import InputError from '@/components/input-error';
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
import type { EmployeeFull, StatusOption } from '@/types/employee';

export type CustomFieldDef = {
    key: string;
    label: string;
    type: string;
    options: string[] | null;
    is_required: boolean;
};

export type CustomFieldValues = Record<
    string,
    string | number | boolean | null
>;

type EmployeeFormProps = {
    statuses: StatusOption[];
    employee?: EmployeeFull;
    customFields?: CustomFieldDef[];
    customFieldValues?: CustomFieldValues;
};

const GENDER_OPTIONS: StatusOption[] = [
    { value: 'male', label: 'Laki-laki' },
    { value: 'female', label: 'Perempuan' },
];

const NONE = '__none__';

/**
 * Shared create/edit form for an employee.
 */
export default function EmployeeForm({
    statuses,
    employee,
    customFields = [],
    customFieldValues = {},
}: EmployeeFormProps) {
    const formProps = employee
        ? employees.update.form(employee.id)
        : employees.store.form();

    const [gender, setGender] = useState(employee?.gender ?? '');
    const [status, setStatus] = useState(employee?.status ?? '');
    const [birthDate, setBirthDate] = useState(employee?.birth_date ?? '');
    const [joinDate, setJoinDate] = useState(employee?.join_date ?? '');

    const [customValues, setCustomValues] = useState<Record<string, string>>(
        () =>
            Object.fromEntries(
                customFields.map((field) => {
                    const raw = customFieldValues[field.key];
                    const value =
                        field.type === 'checkbox'
                            ? raw === true || raw === 1 || raw === '1'
                                ? '1'
                                : '0'
                            : raw === null || raw === undefined
                              ? ''
                              : String(raw);

                    return [field.key, value];
                }),
            ),
    );

    function setCustom(key: string, value: string) {
        setCustomValues((prev) => ({ ...prev, [key]: value }));
    }

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
                            <input
                                type="hidden"
                                name="birth_date"
                                value={birthDate}
                            />
                            <DatePicker
                                id="birth_date"
                                value={birthDate}
                                onChange={setBirthDate}
                            />
                            <InputError message={errors.birth_date} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="join_date">Tanggal Bergabung</Label>
                            <input
                                type="hidden"
                                name="join_date"
                                value={joinDate}
                            />
                            <DatePicker
                                id="join_date"
                                value={joinDate}
                                onChange={setJoinDate}
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
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                            />
                            <InputError message={errors.address} />
                        </div>

                        {customFields.length > 0 && (
                            <div className="grid gap-5 border-t pt-5 md:col-span-2 md:grid-cols-2">
                                <div className="md:col-span-2">
                                    <h3 className="text-sm font-semibold text-foreground">
                                        Data Tambahan
                                    </h3>
                                    <p className="text-xs text-muted-foreground">
                                        Field khusus yang dikonfigurasi untuk
                                        perusahaan ini.
                                    </p>
                                </div>

                                {customFields.map((field) => {
                                    const name = `custom_fields[${field.key}]`;
                                    const error =
                                        errors[`custom_fields.${field.key}`];
                                    const value = customValues[field.key] ?? '';

                                    return (
                                        <div
                                            key={field.key}
                                            className={
                                                field.type === 'textarea'
                                                    ? 'grid gap-2 md:col-span-2'
                                                    : 'grid gap-2'
                                            }
                                        >
                                            <Label htmlFor={`cf_${field.key}`}>
                                                {field.label}{' '}
                                                {field.is_required && (
                                                    <RequiredMark />
                                                )}
                                            </Label>

                                            {field.type === 'select' ? (
                                                <>
                                                    <input
                                                        type="hidden"
                                                        name={name}
                                                        value={value}
                                                    />
                                                    <Select
                                                        value={value || NONE}
                                                        onValueChange={(v) =>
                                                            setCustom(
                                                                field.key,
                                                                v === NONE
                                                                    ? ''
                                                                    : v,
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger
                                                            id={`cf_${field.key}`}
                                                            className="w-full"
                                                        >
                                                            <SelectValue placeholder="Pilih" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem
                                                                value={NONE}
                                                            >
                                                                — kosong —
                                                            </SelectItem>
                                                            {(
                                                                field.options ??
                                                                []
                                                            ).map((option) => (
                                                                <SelectItem
                                                                    key={option}
                                                                    value={
                                                                        option
                                                                    }
                                                                >
                                                                    {option}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </>
                                            ) : field.type === 'checkbox' ? (
                                                <>
                                                    <input
                                                        type="hidden"
                                                        name={name}
                                                        value={value || '0'}
                                                    />
                                                    <label className="flex items-center gap-2 text-sm">
                                                        <input
                                                            id={`cf_${field.key}`}
                                                            type="checkbox"
                                                            checked={
                                                                value === '1'
                                                            }
                                                            onChange={(e) =>
                                                                setCustom(
                                                                    field.key,
                                                                    e.target
                                                                        .checked
                                                                        ? '1'
                                                                        : '0',
                                                                )
                                                            }
                                                            className="size-4 rounded border-input"
                                                        />
                                                        Ya
                                                    </label>
                                                </>
                                            ) : field.type === 'textarea' ? (
                                                <textarea
                                                    id={`cf_${field.key}`}
                                                    name={name}
                                                    value={value}
                                                    onChange={(e) =>
                                                        setCustom(
                                                            field.key,
                                                            e.target.value,
                                                        )
                                                    }
                                                    rows={3}
                                                    placeholder={field.label}
                                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                                />
                                            ) : (
                                                <Input
                                                    id={`cf_${field.key}`}
                                                    name={name}
                                                    type={
                                                        field.type === 'number'
                                                            ? 'number'
                                                            : field.type ===
                                                                'date'
                                                              ? 'date'
                                                              : 'text'
                                                    }
                                                    value={value}
                                                    onChange={(e) =>
                                                        setCustom(
                                                            field.key,
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder={field.label}
                                                />
                                            )}

                                            <InputError message={error} />
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </CardContent>
                    <CardFooter className="flex justify-end gap-3">
                        <Button asChild variant="secondary" type="button">
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
