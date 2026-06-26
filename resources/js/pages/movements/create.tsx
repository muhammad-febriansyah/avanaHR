import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import movements from '@/actions/App/Http/Controllers/EmployeeMovementController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
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
type IdOption = { id: number; label: string };

type CreateProps = {
    employees: IdOption[];
    types: Option[];
    options: {
        positions: IdOption[];
        departments: IdOption[];
        jobGrades: IdOption[];
        managers: IdOption[];
        employmentTypes: Option[];
    };
};

type MovementForm = {
    employee_id: string;
    type: string;
    effective_date: string;
    position_id: string;
    department_id: string;
    job_grade_id: string;
    manager_id: string;
    employment_type: string;
    reason: string;
};

const NONE = '__none__';

const emptyForm: MovementForm = {
    employee_id: '',
    type: '',
    effective_date: '',
    position_id: '',
    department_id: '',
    job_grade_id: '',
    manager_id: '',
    employment_type: '',
    reason: '',
};

export default function MovementsCreate({
    employees,
    types,
    options,
}: CreateProps) {
    useFlashToast();

    const form = useForm<MovementForm>(emptyForm);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        form.post(movements.store.url());
    }

    return (
        <>
            <Head title="Buat Movement" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Buat Movement"
                    description="Catat mutasi, promosi, demosi, skorsing, atau exit karyawan."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="flex flex-col gap-1">
                                <h3 className="text-sm font-semibold text-navy">
                                    Karyawan & Tipe
                                </h3>
                            </div>

                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
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
                                    <Label htmlFor="type">
                                        Tipe Movement <RequiredMark />
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
                                            <SelectValue placeholder="Pilih tipe" />
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
                            </div>

                            <div className="flex flex-col gap-1 border-t border-border/50 pt-5">
                                <h3 className="text-sm font-semibold text-navy">
                                    Perubahan (opsional)
                                </h3>
                                <p className="text-xs text-muted-foreground">
                                    Isi hanya kolom yang berubah. Biarkan "tidak
                                    diubah" untuk mempertahankan data saat ini.
                                </p>
                            </div>

                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="position_id">Posisi</Label>
                                    <Combobox
                                        id="position_id"
                                        value={form.data.position_id || NONE}
                                        onChange={(value) =>
                                            form.setData(
                                                'position_id',
                                                value === NONE ? '' : value,
                                            )
                                        }
                                        options={[
                                            {
                                                value: NONE,
                                                label: '— tidak diubah —',
                                            },
                                            ...options.positions.map(
                                                (option) => ({
                                                    value: String(option.id),
                                                    label: option.label,
                                                }),
                                            ),
                                        ]}
                                        placeholder="— tidak diubah —"
                                        searchPlaceholder="Cari posisi…"
                                        emptyText="Posisi tidak ditemukan"
                                    />
                                    <InputError
                                        message={form.errors.position_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="department_id">
                                        Departemen
                                    </Label>
                                    <Combobox
                                        id="department_id"
                                        value={form.data.department_id || NONE}
                                        onChange={(value) =>
                                            form.setData(
                                                'department_id',
                                                value === NONE ? '' : value,
                                            )
                                        }
                                        options={[
                                            {
                                                value: NONE,
                                                label: '— tidak diubah —',
                                            },
                                            ...options.departments.map(
                                                (option) => ({
                                                    value: String(option.id),
                                                    label: option.label,
                                                }),
                                            ),
                                        ]}
                                        placeholder="— tidak diubah —"
                                        searchPlaceholder="Cari departemen…"
                                        emptyText="Departemen tidak ditemukan"
                                    />
                                    <InputError
                                        message={form.errors.department_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="job_grade_id">
                                        Job Grade
                                    </Label>
                                    <Combobox
                                        id="job_grade_id"
                                        value={form.data.job_grade_id || NONE}
                                        onChange={(value) =>
                                            form.setData(
                                                'job_grade_id',
                                                value === NONE ? '' : value,
                                            )
                                        }
                                        options={[
                                            {
                                                value: NONE,
                                                label: '— tidak diubah —',
                                            },
                                            ...options.jobGrades.map(
                                                (option) => ({
                                                    value: String(option.id),
                                                    label: option.label,
                                                }),
                                            ),
                                        ]}
                                        placeholder="— tidak diubah —"
                                        searchPlaceholder="Cari job grade…"
                                        emptyText="Job grade tidak ditemukan"
                                    />
                                    <InputError
                                        message={form.errors.job_grade_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="manager_id">Atasan</Label>
                                    <Combobox
                                        id="manager_id"
                                        value={form.data.manager_id || NONE}
                                        onChange={(value) =>
                                            form.setData(
                                                'manager_id',
                                                value === NONE ? '' : value,
                                            )
                                        }
                                        options={[
                                            {
                                                value: NONE,
                                                label: '— tidak diubah —',
                                            },
                                            ...options.managers.map(
                                                (option) => ({
                                                    value: String(option.id),
                                                    label: option.label,
                                                }),
                                            ),
                                        ]}
                                        placeholder="— tidak diubah —"
                                        searchPlaceholder="Cari atasan…"
                                        emptyText="Atasan tidak ditemukan"
                                    />
                                    <InputError
                                        message={form.errors.manager_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="employment_type">
                                        Tipe Kerja
                                    </Label>
                                    <Select
                                        value={
                                            form.data.employment_type || NONE
                                        }
                                        onValueChange={(value) =>
                                            form.setData(
                                                'employment_type',
                                                value === NONE ? '' : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="employment_type"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="— tidak diubah —" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>
                                                — tidak diubah —
                                            </SelectItem>
                                            {options.employmentTypes.map(
                                                (option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.employment_type}
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
                                <Link href={movements.index.url()}>
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
