import { Head, Link, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    FileText,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import employeeDocuments from '@/actions/App/Http/Controllers/EmployeeDocumentController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
    file_url: string | null;
};

type Filters = { search?: string; document_type?: string; status?: string };

type IndexProps = {
    documents: Paginator<DocumentRow>;
    filters: Filters;
    types: Option[];
    accessLevels: Option[];
};

const ALL = 'all';

const EXPIRY_STYLES: Record<string, string> = {
    valid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    expiring:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
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

export default function EmployeeDocumentsIndex({
    documents: paginator,
    filters = {},
    types,
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

    function handleDelete(id: number) {
        router.delete(employeeDocuments.destroy.url(id), {
            preserveScroll: true,
        });
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
                    <Button asChild>
                        <Link href={employeeDocuments.create.url()}>
                            <Plus />
                            Tambah Dokumen
                        </Link>
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
                                    go({
                                        document_type:
                                            value === ALL ? undefined : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="Semua Jenis" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>
                                        Semua Jenis
                                    </SelectItem>
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
                            <Select
                                value={status}
                                onValueChange={(value) => {
                                    setStatus(value);
                                    go({
                                        status:
                                            value === ALL ? undefined : value,
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>
                                        Semua Status
                                    </SelectItem>
                                    <SelectItem value="valid">
                                        Berlaku
                                    </SelectItem>
                                    <SelectItem value="expiring">
                                        Segera Habis
                                    </SelectItem>
                                    <SelectItem value="expired">
                                        Kedaluwarsa
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead>Nomor</TableHead>
                                        <TableHead>Berlaku s.d.</TableHead>
                                        <TableHead>Akses</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="py-12"
                                            >
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
                                        rows.map((item, index) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="text-muted-foreground tabular-nums">
                                                    {(paginator.from ?? 1) +
                                                        index}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {item.employee_name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {label(
                                                        types,
                                                        item.document_type,
                                                    )}
                                                </TableCell>
                                                <TableCell className="font-mono text-xs">
                                                    <div className="flex flex-col gap-1">
                                                        <span>
                                                            {item.number ?? '-'}
                                                        </span>
                                                        {item.file_url && (
                                                            <a
                                                                href={
                                                                    item.file_url
                                                                }
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="font-sans font-medium text-primary underline-offset-2 hover:underline"
                                                            >
                                                                Unduh
                                                            </a>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-sm tabular-nums">
                                                            {item.expired_at ??
                                                                '-'}
                                                        </span>
                                                        <Badge
                                                            variant="secondary"
                                                            className={
                                                                EXPIRY_STYLES[
                                                                    item
                                                                        .expiry_status
                                                                ]
                                                            }
                                                        >
                                                            {
                                                                EXPIRY_LABELS[
                                                                    item
                                                                        .expiry_status
                                                                ]
                                                            }
                                                        </Badge>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {ACCESS_LABELS[
                                                        item.access_level
                                                    ] ?? item.access_level}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="success"
                                                        >
                                                            <Link
                                                                href={employeeDocuments.edit.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Dokumen"
                                                            description={`Yakin hapus dokumen ${label(types, item.document_type)} milik ${item.employee_name}?`}
                                                            confirmLabel="Hapus"
                                                            onConfirm={() =>
                                                                handleDelete(
                                                                    item.id,
                                                                )
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
                                    variant="secondary"
                                    size="sm"
                                    disabled={!paginator.prev_page_url}
                                    onClick={() =>
                                        paginator.prev_page_url &&
                                        router.get(
                                            paginator.prev_page_url,
                                            {},
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
                                        )
                                    }
                                >
                                    <ChevronLeft />
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={!paginator.next_page_url}
                                    onClick={() =>
                                        paginator.next_page_url &&
                                        router.get(
                                            paginator.next_page_url,
                                            {},
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
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
        </>
    );
}
