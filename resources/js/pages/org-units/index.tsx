import { Head, router, useForm } from '@inertiajs/react';
import { Building2, Network, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import departments from '@/actions/App/Http/Controllers/DepartmentController';
import positions from '@/actions/App/Http/Controllers/PositionController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Option = { id: number; name: string };

type Department = {
    id: number;
    code: string;
    name: string;
    parent_id: number | null;
    parent_name: string | null;
    positions_count: number;
};

type Position = {
    id: number;
    code: string;
    name: string;
    department_id: number;
    department_name: string | null;
    job_level_id: number | null;
    job_level_name: string | null;
    job_grade_id: number | null;
    job_grade_name: string | null;
};

type IndexProps = {
    departments: Department[];
    positions: Position[];
    options: {
        departments: Option[];
        jobLevels: Option[];
        jobGrades: Option[];
    };
};

const NONE = 'none';

type DepartmentForm = { code: string; name: string; parent_id: string };
type PositionForm = {
    code: string;
    name: string;
    department_id: string;
    job_level_id: string;
    job_grade_id: string;
};

const emptyDepartment: DepartmentForm = { code: '', name: '', parent_id: NONE };
const emptyPosition: PositionForm = {
    code: '',
    name: '',
    department_id: '',
    job_level_id: NONE,
    job_grade_id: NONE,
};

function toId(value: string): number | null {
    return value === NONE || value === '' ? null : Number(value);
}

export default function OrgUnitsIndex({
    departments: deptRows,
    positions: posRows,
    options,
}: IndexProps) {
    return (
        <>
            <Head title="Departemen & Posisi" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Departemen & Posisi"
                    description="Kelola struktur unit organisasi: departemen dan posisi jabatan."
                />

                <DepartmentSection rows={deptRows} options={options} />
                <PositionSection rows={posRows} options={options} />
            </div>
        </>
    );
}

function DepartmentSection({
    rows,
    options,
}: {
    rows: Department[];
    options: IndexProps['options'];
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Department | null>(null);
    const form = useForm<DepartmentForm>(emptyDepartment);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyDepartment);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Department) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            code: item.code,
            name: item.name,
            parent_id: item.parent_id?.toString() ?? NONE,
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform(() => ({
            code: form.data.code,
            name: form.data.name,
            parent_id: toId(form.data.parent_id),
        }));

        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(departments.update.url(editing.id), opts);
        } else {
            form.post(departments.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(departments.destroy.url(id), { preserveScroll: true });
    }

    // A department cannot be its own parent.
    const parentOptions = options.departments.filter(
        (option) => option.id !== editing?.id,
    );

    return (
        <Card className="gap-0 py-0">
            <CardContent className="p-5">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-sm font-semibold">Departemen</h2>
                    <Button size="sm" onClick={openCreate}>
                        <Plus />
                        Tambah Departemen
                    </Button>
                </div>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Kode</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>Induk</TableHead>
                                <TableHead>Posisi</TableHead>
                                <TableHead className="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-12">
                                        <EmptyState
                                            icon={
                                                <Network className="size-6" />
                                            }
                                            text="Belum ada departemen"
                                        />
                                    </TableCell>
                                </TableRow>
                            ) : (
                                rows.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">
                                            {item.code}
                                        </TableCell>
                                        <TableCell>{item.name}</TableCell>
                                        <TableCell>
                                            {item.parent_name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {item.positions_count}
                                        </TableCell>
                                        <TableCell>
                                            <RowActions
                                                onEdit={() => openEdit(item)}
                                                deleteTitle="Hapus Departemen"
                                                deleteDescription={`Yakin ingin menghapus "${item.name}"?`}
                                                onDelete={() =>
                                                    handleDelete(item.id)
                                                }
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editing
                                ? 'Ubah Departemen'
                                : 'Tambah Departemen'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="department-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <Field
                            id="dept-code"
                            label="Kode"
                            value={form.data.code}
                            onChange={(value) => form.setData('code', value)}
                            placeholder="Mis. DEP001"
                            error={form.errors.code}
                            autoFocus
                        />
                        <Field
                            id="dept-name"
                            label="Nama"
                            value={form.data.name}
                            onChange={(value) => form.setData('name', value)}
                            placeholder="Mis. Human Resources"
                            error={form.errors.name}
                        />
                        <div className="grid gap-2">
                            <Label htmlFor="dept-parent">Departemen Induk</Label>
                            <Select
                                value={form.data.parent_id}
                                onValueChange={(value) =>
                                    form.setData('parent_id', value)
                                }
                            >
                                <SelectTrigger id="dept-parent" className="w-full">
                                    <SelectValue placeholder="Tanpa induk" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>
                                        Tanpa induk
                                    </SelectItem>
                                    {parentOptions.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={option.id.toString()}
                                        >
                                            {option.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.errors.parent_id && (
                                <p className="text-sm text-destructive">
                                    {form.errors.parent_id}
                                </p>
                            )}
                        </div>
                    </form>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setOpen(false)}
                        >
                            <X />
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="department-form"
                            disabled={form.processing}
                        >
                            {editing ? 'Simpan Perubahan' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
}

function PositionSection({
    rows,
    options,
}: {
    rows: Position[];
    options: IndexProps['options'];
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Position | null>(null);
    const form = useForm<PositionForm>(emptyPosition);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyPosition);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Position) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            code: item.code,
            name: item.name,
            department_id: item.department_id.toString(),
            job_level_id: item.job_level_id?.toString() ?? NONE,
            job_grade_id: item.job_grade_id?.toString() ?? NONE,
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform(() => ({
            code: form.data.code,
            name: form.data.name,
            department_id: toId(form.data.department_id),
            job_level_id: toId(form.data.job_level_id),
            job_grade_id: toId(form.data.job_grade_id),
        }));

        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(positions.update.url(editing.id), opts);
        } else {
            form.post(positions.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(positions.destroy.url(id), { preserveScroll: true });
    }

    return (
        <Card className="gap-0 py-0">
            <CardContent className="p-5">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-sm font-semibold">Posisi</h2>
                    <Button
                        size="sm"
                        onClick={openCreate}
                        disabled={options.departments.length === 0}
                    >
                        <Plus />
                        Tambah Posisi
                    </Button>
                </div>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Kode</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>Departemen</TableHead>
                                <TableHead>Level</TableHead>
                                <TableHead>Grade</TableHead>
                                <TableHead className="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-12">
                                        <EmptyState
                                            icon={
                                                <Building2 className="size-6" />
                                            }
                                            text="Belum ada posisi"
                                        />
                                    </TableCell>
                                </TableRow>
                            ) : (
                                rows.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">
                                            {item.code}
                                        </TableCell>
                                        <TableCell>{item.name}</TableCell>
                                        <TableCell>
                                            {item.department_name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {item.job_level_name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {item.job_grade_name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            <RowActions
                                                onEdit={() => openEdit(item)}
                                                deleteTitle="Hapus Posisi"
                                                deleteDescription={`Yakin ingin menghapus "${item.name}"?`}
                                                onDelete={() =>
                                                    handleDelete(item.id)
                                                }
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Ubah Posisi' : 'Tambah Posisi'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="position-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <Field
                            id="pos-code"
                            label="Kode"
                            value={form.data.code}
                            onChange={(value) => form.setData('code', value)}
                            placeholder="Mis. POS001"
                            error={form.errors.code}
                            autoFocus
                        />
                        <Field
                            id="pos-name"
                            label="Nama"
                            value={form.data.name}
                            onChange={(value) => form.setData('name', value)}
                            placeholder="Mis. Software Engineer"
                            error={form.errors.name}
                        />
                        <div className="grid gap-2">
                            <Label htmlFor="pos-dept">Departemen</Label>
                            <Select
                                value={form.data.department_id}
                                onValueChange={(value) =>
                                    form.setData('department_id', value)
                                }
                            >
                                <SelectTrigger id="pos-dept" className="w-full">
                                    <SelectValue placeholder="Pilih departemen" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.departments.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={option.id.toString()}
                                        >
                                            {option.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.errors.department_id && (
                                <p className="text-sm text-destructive">
                                    {form.errors.department_id}
                                </p>
                            )}
                        </div>
                        <OptionalSelect
                            id="pos-level"
                            label="Level Jabatan"
                            value={form.data.job_level_id}
                            onChange={(value) =>
                                form.setData('job_level_id', value)
                            }
                            options={options.jobLevels}
                            error={form.errors.job_level_id}
                        />
                        <OptionalSelect
                            id="pos-grade"
                            label="Grade"
                            value={form.data.job_grade_id}
                            onChange={(value) =>
                                form.setData('job_grade_id', value)
                            }
                            options={options.jobGrades}
                            error={form.errors.job_grade_id}
                        />
                    </form>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setOpen(false)}
                        >
                            <X />
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="position-form"
                            disabled={form.processing}
                        >
                            {editing ? 'Simpan Perubahan' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
}

function Field({
    id,
    label,
    value,
    onChange,
    placeholder,
    error,
    autoFocus,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    error?: string;
    autoFocus?: boolean;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                autoFocus={autoFocus}
            />
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}

function OptionalSelect({
    id,
    label,
    value,
    onChange,
    options,
    error,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: Option[];
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger id={id} className="w-full">
                    <SelectValue placeholder="Tidak ada" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={NONE}>Tidak ada</SelectItem>
                    {options.map((option) => (
                        <SelectItem
                            key={option.id}
                            value={option.id.toString()}
                        >
                            {option.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}

function EmptyState({
    icon,
    text,
}: {
    icon: React.ReactNode;
    text: string;
}) {
    return (
        <div className="flex flex-col items-center justify-center gap-3 text-center">
            <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                {icon}
            </div>
            <p className="text-sm text-muted-foreground">{text}</p>
        </div>
    );
}

function RowActions({
    onEdit,
    deleteTitle,
    deleteDescription,
    onDelete,
}: {
    onEdit: () => void;
    deleteTitle: string;
    deleteDescription: string;
    onDelete: () => void;
}) {
    return (
        <div className="flex items-center justify-end gap-2">
            <Button size="sm" variant="outline" onClick={onEdit}>
                <Pencil />
                Edit
            </Button>
            <ConfirmDialog
                title={deleteTitle}
                description={deleteDescription}
                confirmLabel="Hapus"
                onConfirm={onDelete}
                trigger={
                    <Button size="sm" variant="destructive">
                        <Trash2 />
                        Hapus
                    </Button>
                }
            />
        </div>
    );
}
