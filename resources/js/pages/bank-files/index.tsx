import { Head, router, useForm } from '@inertiajs/react';
import {
    Banknote,
    ChevronLeft,
    ChevronRight,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import bankFiles from '@/actions/App/Http/Controllers/BankFileController';
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
type RunOption = { id: number; run_no: string };

type Row = {
    id: number;
    run_no: string | null;
    bank_code: string;
    format: string | null;
    total: number;
    file_path: string | null;
};

type Filters = { run_id?: string };

type IndexProps = {
    bankFiles: Paginator<Row>;
    filters: Filters;
    banks: Option[];
    formats: Option[];
    options: { runs: RunOption[] };
};

const ALL_RUN = 'all';

const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
});

type BankFileForm = { run_id: string; bank_code: string; format: string };

const emptyForm: BankFileForm = {
    run_id: '',
    bank_code: 'BCA',
    format: 'csv',
};

export default function BankFilesIndex({
    bankFiles: paginator,
    filters = {},
    banks,
    formats,
    options,
}: IndexProps) {
    useFlashToast();

    const [runId, setRunId] = useState(filters.run_id ?? ALL_RUN);

    function filterByRun(value: string) {
        setRunId(value);
        router.get(
            bankFiles.index.url(),
            { run_id: value === ALL_RUN ? undefined : value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const [open, setOpen] = useState(false);
    const form = useForm<BankFileForm>(emptyForm);

    function openCreate() {
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        form.post(bankFiles.store.url(), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    function handleDelete(id: number) {
        router.delete(bankFiles.destroy.url(id), { preserveScroll: true });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="File Bank" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="File Bank"
                    description="Buat file transfer bank untuk pembayaran payroll."
                >
                    <Button
                        onClick={openCreate}
                        disabled={options.runs.length === 0}
                    >
                        <Plus />
                        Buat File Bank
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Select value={runId} onValueChange={filterByRun}>
                                <SelectTrigger className="w-full sm:w-56">
                                    <SelectValue placeholder="Semua Run" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_RUN}>
                                        Semua Run
                                    </SelectItem>
                                    {options.runs.map((run) => (
                                        <SelectItem
                                            key={run.id}
                                            value={String(run.id)}
                                        >
                                            {run.run_no}
                                        </SelectItem>
                                    ))}
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
                                        <TableHead>Run</TableHead>
                                        <TableHead>Bank</TableHead>
                                        <TableHead>Format</TableHead>
                                        <TableHead className="text-right">
                                            Total
                                        </TableHead>
                                        <TableHead>Berkas</TableHead>
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
                                                        <Banknote className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada file bank
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
                                                <TableCell className="font-medium">
                                                    {item.run_no}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">
                                                        {item.bank_code}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="uppercase">
                                                    {item.format}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {rupiah.format(item.total)}
                                                </TableCell>
                                                <TableCell className="max-w-[220px] truncate text-xs text-muted-foreground">
                                                    {item.file_path ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end">
                                                        <ConfirmDialog
                                                            title="Hapus File Bank"
                                                            description={`Yakin ingin menghapus file bank "${item.bank_code}" untuk ${item.run_no}?`}
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
                                {paginator.total} file
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

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Buat File Bank</DialogTitle>
                    </DialogHeader>

                    <form
                        id="bank-file-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="bf-run">Proses Payroll</Label>
                            <Select
                                value={form.data.run_id}
                                onValueChange={(value) =>
                                    form.setData('run_id', value)
                                }
                            >
                                <SelectTrigger id="bf-run" className="w-full">
                                    <SelectValue placeholder="Pilih run" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.runs.map((run) => (
                                        <SelectItem
                                            key={run.id}
                                            value={String(run.id)}
                                        >
                                            {run.run_no}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.errors.run_id && (
                                <p className="text-sm text-destructive">
                                    {form.errors.run_id}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="bf-bank">Bank</Label>
                                <Select
                                    value={form.data.bank_code}
                                    onValueChange={(value) =>
                                        form.setData('bank_code', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="bf-bank"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {banks.map((option) => (
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
                                <Label htmlFor="bf-format">Format</Label>
                                <Select
                                    value={form.data.format}
                                    onValueChange={(value) =>
                                        form.setData('format', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="bf-format"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {formats.map((option) => (
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

                        <p className="text-xs text-muted-foreground">
                            Total transfer diambil dari nilai net proses payroll
                            terpilih.
                        </p>
                    </form>

                    <DialogFooter>
                        <Button
                            variant="secondary"
                            type="button"
                            onClick={() => setOpen(false)}
                        >
                            <X />
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="bank-file-form"
                            disabled={form.processing}
                        >
                            Buat
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
