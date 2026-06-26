import { Head, Link, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Pencil,
    Plus,
    SlidersHorizontal,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import customFields from '@/actions/App/Http/Controllers/CustomFieldController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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

export default function CustomFieldsIndex({
    fields: paginator,
    filters,
    entities,
    types,
}: IndexProps) {
    useFlashToast();

    const [entity, setEntity] = useState(filters.entity_type ?? ALL);

    function go(value: string) {
        setEntity(value);
        router.get(
            customFields.index.url(),
            { entity_type: value === ALL ? undefined : value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
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
                    <Button asChild>
                        <Link href={customFields.create.url()}>
                            <Plus />
                            Tambah Field
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <Select value={entity} onValueChange={go}>
                            <SelectTrigger className="w-full sm:w-56">
                                <SelectValue placeholder="Semua Entitas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>
                                    Semua Entitas
                                </SelectItem>
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

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Entitas</TableHead>
                                        <TableHead>Label</TableHead>
                                        <TableHead>Kunci</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Wajib</TableHead>
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
                                                        <SlidersHorizontal className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada custom field
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
                                                <TableCell className="text-muted-foreground">
                                                    {label(
                                                        entities,
                                                        item.entity_type,
                                                    )}
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
                                                            (
                                                            {
                                                                item.options
                                                                    .length
                                                            }{' '}
                                                            opsi)
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
                                                            asChild
                                                            size="sm"
                                                            variant="success"
                                                        >
                                                            <Link
                                                                href={customFields.edit.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Custom Field"
                                                            description={`Yakin hapus field "${item.label}"? Nilai tersimpan ikut hilang.`}
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
                                {paginator.total} field
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
