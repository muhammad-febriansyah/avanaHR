import { Head, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Pencil,
    Plus,
    SlidersHorizontal,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import customFields from '@/actions/App/Http/Controllers/CustomFieldController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
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

type Option = { value: string; label: string };

type FieldRow = {
    id: number;
    entity_type: string;
    key: string;
    label: string;
    type: string;
    options: string[];
    is_required: boolean;
    order: number;
};

type Filters = { entity_type?: string | null };

type IndexProps = {
    fields: Paginator<FieldRow>;
    filters: Filters;
    entities: Option[];
    types: Option[];
};

const ALL = 'all';

function label(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

function slugify(value: string): string {
    return value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .replace(/^([0-9])/, 'f_$1');
}

type FieldForm = {
    entity_type: string;
    key: string;
    label: string;
    type: string;
    options: string;
    is_required: boolean;
    order: string;
};

export default function CustomFieldsIndex({
    fields: paginator,
    filters,
    entities,
    types,
}: IndexProps) {
    useFlashToast();

    const [entity, setEntity] = useState(filters.entity_type ?? ALL);
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<FieldRow | null>(null);
    const form = useForm<FieldForm>({
        entity_type: 'employee',
        key: '',
        label: '',
        type: 'text',
        options: '',
        is_required: false,
        order: '0',
    });

    function go(value: string) {
        setEntity(value);
        router.get(
            customFields.index.url(),
            { entity_type: value === ALL ? undefined : value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openCreate() {
        setEditing(null);
        form.setData({
            entity_type: 'employee',
            key: '',
            label: '',
            type: 'text',
            options: '',
            is_required: false,
            order: '0',
        });
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: FieldRow) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            entity_type: item.entity_type,
            key: item.key,
            label: item.label,
            type: item.type,
            options: item.options.join('\n'),
            is_required: item.is_required,
            order: String(item.order),
        });
        setOpen(true);
    }

    function onLabelChange(value: string) {
        const next: Partial<FieldForm> = { label: value };
        // Auto-fill the key from the label while creating, until edited manually.
        if (!editing && (form.data.key === '' || form.data.key === slugify(form.data.label))) {
            next.key = slugify(value);
        }
        form.setData({ ...form.data, ...next });
    }

    function payloadOptions(): string[] {
        return form.data.options
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            options: payloadOptions(),
        }));
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(customFields.update.url(editing.id), opts);
        } else {
            form.post(customFields.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(customFields.destroy.url(id), { preserveScroll: true });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Custom Field" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Custom Field"
                    description="Tambah field data sendiri (mis. ukuran seragam) tanpa ubah sistem."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Field
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <Select value={entity} onValueChange={go}>
                            <SelectTrigger className="w-full sm:w-56">
                                <SelectValue placeholder="Semua Entitas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>Semua Entitas</SelectItem>
                                {entities.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Entitas</TableHead>
                                        <TableHead>Label</TableHead>
                                        <TableHead>Kunci</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Wajib</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <SlidersHorizontal className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada custom field
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="text-muted-foreground">
                                                    {label(entities, item.entity_type)}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {item.label}
                                                </TableCell>
                                                <TableCell className="font-mono text-xs">
                                                    {item.key}
                                                </TableCell>
                                                <TableCell>
                                                    {label(types, item.type)}
                                                    {item.type === 'select' &&
                                                    item.options.length > 0 ? (
                                                        <span className="text-xs text-muted-foreground">
                                                            {' '}
                                                            ({item.options.length} opsi)
                                                        </span>
                                                    ) : null}
                                                </TableCell>
                                                <TableCell>
                                                    {item.is_required ? (
                                                        <Badge
                                                            variant="secondary"
                                                            className="bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400"
                                                        >
                                                            Wajib
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">
                                                            Opsional
                                                        </span>
                                                    )}
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
                                                            title="Hapus Custom Field"
                                                            description={`Yakin hapus field "${item.label}"? Nilai tersimpan ikut hilang.`}
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
                                {paginator.total} field
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
                            {editing ? 'Ubah Custom Field' : 'Tambah Custom Field'}
                        </DialogTitle>
                    </DialogHeader>

                    <form id="cf-form" onSubmit={handleSubmit} className="grid gap-4">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="cf-entity">Entitas</Label>
                                <Select
                                    value={form.data.entity_type}
                                    onValueChange={(value) =>
                                        form.setData('entity_type', value)
                                    }
                                    disabled={!!editing}
                                >
                                    <SelectTrigger id="cf-entity" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {entities.map((option) => (
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
                                <Label htmlFor="cf-type">Tipe</Label>
                                <Select
                                    value={form.data.type}
                                    onValueChange={(value) => form.setData('type', value)}
                                >
                                    <SelectTrigger id="cf-type" className="w-full">
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
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="cf-label">Label</Label>
                            <Input
                                id="cf-label"
                                value={form.data.label}
                                onChange={(e) => onLabelChange(e.target.value)}
                                placeholder="Mis. Ukuran Seragam"
                            />
                            {form.errors.label && (
                                <p className="text-sm text-destructive">
                                    {form.errors.label}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="cf-key">Kunci</Label>
                            <Input
                                id="cf-key"
                                value={form.data.key}
                                onChange={(e) => form.setData('key', e.target.value)}
                                placeholder="ukuran_seragam"
                                disabled={!!editing}
                                className="font-mono"
                            />
                            {form.errors.key && (
                                <p className="text-sm text-destructive">
                                    {form.errors.key}
                                </p>
                            )}
                        </div>

                        {form.data.type === 'select' && (
                            <div className="grid gap-2">
                                <Label htmlFor="cf-options">
                                    Pilihan (satu per baris)
                                </Label>
                                <textarea
                                    id="cf-options"
                                    value={form.data.options}
                                    onChange={(e) =>
                                        form.setData('options', e.target.value)
                                    }
                                    rows={4}
                                    placeholder={'S\nM\nL\nXL'}
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                />
                            </div>
                        )}

                        <div className="flex items-center justify-between">
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={form.data.is_required}
                                    onCheckedChange={(checked) =>
                                        form.setData('is_required', checked === true)
                                    }
                                />
                                Wajib diisi
                            </label>
                            <div className="flex items-center gap-2">
                                <Label htmlFor="cf-order" className="text-sm">
                                    Urutan
                                </Label>
                                <Input
                                    id="cf-order"
                                    type="number"
                                    min="0"
                                    max="999"
                                    value={form.data.order}
                                    onChange={(e) => form.setData('order', e.target.value)}
                                    className="w-20"
                                />
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
                        <Button type="submit" form="cf-form" disabled={form.processing}>
                            {editing ? 'Simpan Perubahan' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
