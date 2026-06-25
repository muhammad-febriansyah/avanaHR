import { Head, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    FileText,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import employeeDocuments from '@/actions/App/Http/Controllers/EmployeeDocumentController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
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
import { useFlashToast } from '@/hooks/use-flash-toast';
import type { Paginator } from '@/types/employee';

type Option = { value: string; label: string };
type EmployeeOption = { id: number; label: string };

type DocumentRow = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    document_type: string;
    number: string | null;
    issued_at: string | null;
    expired_at: string | null;
    access_level: string;
    expiry_status: string;
};

type Filters = { search?: string; document_type?: string; status?: string };

type IndexProps = {
    documents: Paginator<DocumentRow>;
    filters: Filters;
    types: Option[];
    accessLevels: Option[];
    options: { employees: EmployeeOption[] };
};

const ALL = 'all';

const EXPIRY_STYLES: Record<string, string> = {
    valid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    expiring: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    expired: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    none: 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
};

const EXPIRY_LABELS: Record<string, string> = {
    valid: 'Berlaku',
    expiring: 'Segera Habis',
    expired: 'Kedaluwarsa',
    none: 'Tanpa Masa',
};

const ACCESS_LABELS: Record<string, string> = {
    public: 'Publik',
    internal: 'Internal',
    confidential: 'Rahasia',
};

function label(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

type DocForm = {
    employee_id: string;
    document_type: string;
    number: string;
    issued_at: string;
    expired_at: string;
    reminder_days: string;
    access_level: string;
};

const emptyForm: DocForm = {
    employee_id: '',
    document_type: 'ktp',
    number: '',
    issued_at: '',
    expired_at: '',
    reminder_days: '30',
    access_level: 'internal',
};

export default function EmployeeDocumentsIndex({
    documents: paginator,
    filters = {},
    types,
    accessLevels,
    options,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.document_type ?? ALL);
    const [status, setStatus] = useState(filters.status ?? ALL);
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            employeeDocuments.index.url(),
            {
                search: search || undefined,
                document_type: type === ALL ? undefined : type,
                status: status === ALL ? undefined : status,
                ...extra,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = setTimeout(() => go({}), 350);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<DocumentRow | null>(null);
    const form = useForm<DocForm>(emptyForm);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: DocumentRow) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            employee_id: '',
            document_type: item.document_type,
            number: item.number ?? '',
            issued_at: item.issued_at ?? '',
            expired_at: item.expired_at ?? '',
            reminder_days: '30',
            access_level: item.access_level,
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(employeeDocuments.update.url(editing.id), opts);
        } else {
            form.post(employeeDocuments.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(employeeDocuments.destroy.url(id), { preserveScroll: true });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Dokumen" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Dokumen"
                    description="Registri dokumen karyawan beserta masa berlaku & pengingat."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Dokumen
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nomor / karyawan"
                                className="sm:max-w-xs"
                            />
                            <Select
                                value={type}
                                onValueChange={(value) => {
                                    setType(value);
                                    go({ document_type: value === ALL ? undefined : value });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="Semua Jenis" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Semua Jenis</SelectItem>
                                    {types.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={status}
                                onValueChange={(value) => {
                                    setStatus(value);
                                    go({ status: value === ALL ? undefined : value });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Semua Status</SelectItem>
                                    <SelectItem value="valid">Berlaku</SelectItem>
                                    <SelectItem value="expiring">Segera Habis</SelectItem>
                                    <SelectItem value="expired">Kedaluwarsa</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead>Nomor</TableHead>
                                        <TableHead>Berlaku s.d.</TableHead>
                                        <TableHead>Akses</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <FileText className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada dokumen
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {item.employee_name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {label(types, item.document_type)}
                                                </TableCell>
                                                <TableCell className="font-mono text-xs">
                                                    {item.number ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-sm tabular-nums">
                                                            {item.expired_at ?? '-'}
                                                        </span>
                                                        <Badge
                                                            variant="secondary"
                                                            className={EXPIRY_STYLES[item.expiry_status]}
                                                        >
                                                            {EXPIRY_LABELS[item.expiry_status]}
                                                        </Badge>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {ACCESS_LABELS[item.access_level] ??
                                                        item.access_level}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => openEdit(item)}
                                                        >
                                                            <Pencil />
                                                            Edit
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Dokumen"
                                                            description={`Yakin hapus dokumen ${label(types, item.document_type)} milik ${item.employee_name}?`}
                                                            confirmLabel="Hapus"
                                                            onConfirm={() =>
                                                                handleDelete(item.id)
                                                            }
                                                            trigger={
                                                                <Button
                                                                    size="sm"
                                                                    variant="destructive"
                                                                >
                                                                    <Trash2 />
                                                                    Hapus
                                                                </Button>
                                                            }
                                                        />
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} dokumen
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!paginator.prev_page_url}
                                    onClick={() =>
                                        paginator.prev_page_url &&
                                        router.get(
                                            paginator.prev_page_url,
                                            {},
                                            { preserveState: true, preserveScroll: true },
                                        )
                                    }
                                >
                                    <ChevronLeft />
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!paginator.next_page_url}
                                    onClick={() =>
                                        paginator.next_page_url &&
                                        router.get(
                                            paginator.next_page_url,
                                            {},
                                            { preserveState: true, preserveScroll: true },
                                        )
                                    }
                                >
                                    Berikutnya
                                    <ChevronRight />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Ubah Dokumen' : 'Tambah Dokumen'}
                        </DialogTitle>
                    </DialogHeader>

                    <form id="doc-form" onSubmit={handleSubmit} className="grid gap-4">
                        {editing ? (
                            <div className="rounded-lg border bg-muted/40 p-3 text-sm">
                                <div className="font-medium">{editing.employee_name}</div>
                                <div className="text-muted-foreground">
                                    {editing.employee_no}
                                </div>
                            </div>
                        ) : (
                            <div className="grid gap-2">
                                <Label htmlFor="doc-employee">Karyawan</Label>
                                <Select
                                    value={form.data.employee_id}
                                    onValueChange={(value) =>
                                        form.setData('employee_id', value)
                                    }
                                >
                                    <SelectTrigger id="doc-employee" className="w-full">
                                        <SelectValue placeholder="Pilih karyawan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.employees.map((option) => (
                                            <SelectItem
                                                key={option.id}
                                                value={String(option.id)}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.employee_id && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.employee_id}
                                    </p>
                                )}
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="doc-type">Jenis Dokumen</Label>
                                <Select
                                    value={form.data.document_type}
                                    onValueChange={(value) =>
                                        form.setData('document_type', value)
                                    }
                                >
                                    <SelectTrigger id="doc-type" className="w-full">
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
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="doc-number">Nomor</Label>
                                <Input
                                    id="doc-number"
                                    value={form.data.number}
                                    onChange={(e) => form.setData('number', e.target.value)}
                                    placeholder="Nomor dokumen"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="doc-issued">Tanggal Terbit</Label>
                                <Input
                                    id="doc-issued"
                                    type="date"
                                    value={form.data.issued_at}
                                    onChange={(e) =>
                                        form.setData('issued_at', e.target.value)
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="doc-expired">Berlaku s.d.</Label>
                                <Input
                                    id="doc-expired"
                                    type="date"
                                    value={form.data.expired_at}
                                    onChange={(e) =>
                                        form.setData('expired_at', e.target.value)
                                    }
                                />
                                {form.errors.expired_at && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.expired_at}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="doc-reminder">Ingatkan (hari)</Label>
                                <Input
                                    id="doc-reminder"
                                    type="number"
                                    min="0"
                                    max="365"
                                    value={form.data.reminder_days}
                                    onChange={(e) =>
                                        form.setData('reminder_days', e.target.value)
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="doc-access">Level Akses</Label>
                                <Select
                                    value={form.data.access_level}
                                    onValueChange={(value) =>
                                        form.setData('access_level', value)
                                    }
                                >
                                    <SelectTrigger id="doc-access" className="w-full">
                                        <SelectValue />
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
                            </div>
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
                        <Button type="submit" form="doc-form" disabled={form.processing}>
                            {editing ? 'Simpan Perubahan' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
