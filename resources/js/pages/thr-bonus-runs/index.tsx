import { Head, router, useForm } from '@inertiajs/react';
import { Gift, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import thrBonusRuns from '@/actions/App/Http/Controllers/ThrBonusRunController';
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

type Option = { value: string; label: string };

type Run = {
    id: number;
    type: string;
    period_ref: string | null;
    status: string;
};

type Filters = { type?: string; status?: string };

type IndexProps = {
    runs: Run[];
    filters: Filters;
    types: Option[];
    statuses: Option[];
};

const ALL_TYPE = 'all';
const ALL_STATUS = 'all';

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
    calculated: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    disbursed: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
};

type RunForm = { type: string; period_ref: string; status: string };

const emptyForm: RunForm = { type: 'thr', period_ref: '', status: 'draft' };

function labelOf(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

export default function ThrBonusRunsIndex({
    runs: rows,
    filters = {},
    types,
    statuses,
}: IndexProps) {
    useFlashToast();

    const [type, setType] = useState(filters.type ?? ALL_TYPE);
    const [status, setStatus] = useState(filters.status ?? ALL_STATUS);

    function go(next: { type?: string; status?: string }) {
        const t = next.type ?? type;
        const s = next.status ?? status;
        router.get(
            thrBonusRuns.index.url(),
            {
                type: t === ALL_TYPE ? undefined : t,
                status: s === ALL_STATUS ? undefined : s,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Run | null>(null);
    const form = useForm<RunForm>(emptyForm);

    function openCreate() {
        setEditing(null);
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(item: Run) {
        setEditing(item);
        form.clearErrors();
        form.setData({
            type: item.type,
            period_ref: item.period_ref ?? '',
            status: item.status,
        });
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(thrBonusRuns.update.url(editing.id), opts);
        } else {
            form.post(thrBonusRuns.store.url(), opts);
        }
    }

    function handleDelete(id: number) {
        router.delete(thrBonusRuns.destroy.url(id), { preserveScroll: true });
    }

    return (
        <>
            <Head title="THR & Bonus" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="THR & Bonus"
                    description="Kelola batch perhitungan THR dan bonus."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Run
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Select
                                value={type}
                                onValueChange={(value) => {
                                    setType(value);
                                    go({ type: value });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="Semua Tipe" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_TYPE}>
                                        Semua Tipe
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
                                    go({ status: value });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_STATUS}>
                                        Semua Status
                                    </SelectItem>
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
                        </div>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Periode / Keterangan</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <Gift className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada run THR/Bonus
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="font-medium">
                                                    {labelOf(types, item.type)}
                                                </TableCell>
                                                <TableCell>
                                                    {item.period_ref}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={
                                                            STATUS_STYLES[
                                                                item.status
                                                            ]
                                                        }
                                                    >
                                                        {labelOf(
                                                            statuses,
                                                            item.status,
                                                        )}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                openEdit(item)
                                                            }
                                                        >
                                                            <Pencil />
                                                            Edit
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Run THR/Bonus"
                                                            description={`Yakin ingin menghapus run "${item.period_ref}"?`}
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
                    </CardContent>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Ubah Run THR/Bonus' : 'Tambah Run THR/Bonus'}
                        </DialogTitle>
                    </DialogHeader>

                    <form
                        id="run-form"
                        onSubmit={handleSubmit}
                        className="grid gap-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="tb-type">Tipe</Label>
                            <Select
                                value={form.data.type}
                                onValueChange={(value) =>
                                    form.setData('type', value)
                                }
                            >
                                <SelectTrigger id="tb-type" className="w-full">
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
                            <Label htmlFor="tb-period">
                                Periode / Keterangan
                            </Label>
                            <Input
                                id="tb-period"
                                value={form.data.period_ref}
                                onChange={(event) =>
                                    form.setData(
                                        'period_ref',
                                        event.target.value,
                                    )
                                }
                                placeholder="Mis. Idul Fitri 2026"
                                autoFocus
                            />
                            {form.errors.period_ref && (
                                <p className="text-sm text-destructive">
                                    {form.errors.period_ref}
                                </p>
                            )}
                        </div>

                        {editing && (
                            <div className="grid gap-2">
                                <Label htmlFor="tb-status">Status</Label>
                                <Select
                                    value={form.data.status}
                                    onValueChange={(value) =>
                                        form.setData('status', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="tb-status"
                                        className="w-full"
                                    >
                                        <SelectValue />
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
                            </div>
                        )}
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
                            form="run-form"
                            disabled={form.processing}
                        >
                            {editing ? 'Simpan Perubahan' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
