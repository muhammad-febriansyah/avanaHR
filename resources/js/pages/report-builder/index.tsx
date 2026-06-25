import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Play,
    Plus,
    Trash2,
    Wrench,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import reportBuilder from '@/actions/App/Http/Controllers/ReportBuilderController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

type ColumnOption = { value: string; label: string };
type Source = { value: string; label: string; columns: ColumnOption[] };

type DefinitionRow = {
    id: number;
    name: string;
    source: string;
    source_label: string;
    columns_count: number;
    created_at: string | null;
};

type IndexProps = {
    definitions: Paginator<DefinitionRow>;
    sources: Source[];
};

type BuilderForm = {
    name: string;
    source: string;
    columns: string[];
};

export default function ReportBuilderIndex({ definitions: paginator, sources }: IndexProps) {
    useFlashToast();

    const [open, setOpen] = useState(false);
    const form = useForm<BuilderForm>({
        name: '',
        source: sources[0]?.value ?? '',
        columns: [],
    });

    const activeSource = useMemo(
        () => sources.find((source) => source.value === form.data.source),
        [sources, form.data.source],
    );

    function openCreate() {
        form.setData({ name: '', source: sources[0]?.value ?? '', columns: [] });
        form.clearErrors();
        setOpen(true);
    }

    function toggleColumn(column: string, checked: boolean) {
        form.setData(
            'columns',
            checked
                ? [...form.data.columns, column]
                : form.data.columns.filter((value) => value !== column),
        );
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        form.post(reportBuilder.store.url(), { onSuccess: () => setOpen(false) });
    }

    function handleDelete(id: number) {
        router.delete(reportBuilder.destroy.url(id), { preserveScroll: true });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Report Builder" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Report Builder"
                    description="Susun laporan kustom: pilih sumber data & kolom, lalu jalankan."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Laporan Baru
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama Laporan</TableHead>
                                        <TableHead>Sumber Data</TableHead>
                                        <TableHead>Kolom</TableHead>
                                        <TableHead>Dibuat</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <Wrench className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada laporan tersimpan
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="font-medium">
                                                    {item.name}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {item.source_label}
                                                </TableCell>
                                                <TableCell className="tabular-nums">
                                                    {item.columns_count} kolom
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground tabular-nums">
                                                    {item.created_at}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="outline"
                                                        >
                                                            <Link
                                                                href={reportBuilder.run.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Play />
                                                                Jalankan
                                                            </Link>
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Laporan"
                                                            description={`Yakin hapus laporan "${item.name}"?`}
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
                                {paginator.total} laporan
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
                        <DialogTitle>Laporan Baru</DialogTitle>
                    </DialogHeader>

                    <form id="report-form" onSubmit={handleSubmit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="rb-name">Nama Laporan</Label>
                            <Input
                                id="rb-name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="Mis. Daftar Karyawan Aktif"
                            />
                            {form.errors.name && (
                                <p className="text-sm text-destructive">
                                    {form.errors.name}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="rb-source">Sumber Data</Label>
                            <Select
                                value={form.data.source}
                                onValueChange={(value) =>
                                    form.setData({
                                        ...form.data,
                                        source: value,
                                        columns: [],
                                    })
                                }
                            >
                                <SelectTrigger id="rb-source" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {sources.map((source) => (
                                        <SelectItem
                                            key={source.value}
                                            value={source.value}
                                        >
                                            {source.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid gap-2">
                            <Label>Kolom</Label>
                            <div className="grid grid-cols-2 gap-2 rounded-lg border p-3">
                                {activeSource?.columns.map((column) => (
                                    <label
                                        key={column.value}
                                        className="flex items-center gap-2 text-sm"
                                    >
                                        <Checkbox
                                            checked={form.data.columns.includes(
                                                column.value,
                                            )}
                                            onCheckedChange={(checked) =>
                                                toggleColumn(
                                                    column.value,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        {column.label}
                                    </label>
                                ))}
                            </div>
                            {form.errors.columns && (
                                <p className="text-sm text-destructive">
                                    {form.errors.columns}
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
                        <Button type="submit" form="report-form" disabled={form.processing}>
                            Simpan & Jalankan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
