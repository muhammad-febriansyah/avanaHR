import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    GitBranch,
    Plus,
    Settings2,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import approvalFlows from '@/actions/App/Http/Controllers/ApprovalFlowController';
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

type FlowRow = {
    id: number;
    name: string;
    transaction_type: string;
    is_active: boolean;
    steps_count: number;
};

type IndexProps = {
    flows: Paginator<FlowRow>;
    transactionTypes: Option[];
};

function label(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

type FlowForm = {
    name: string;
    transaction_type: string;
    is_active: boolean;
};

export default function ApprovalFlowsIndex({
    flows: paginator,
    transactionTypes,
}: IndexProps) {
    useFlashToast();

    const [open, setOpen] = useState(false);
    const form = useForm<FlowForm>({
        name: '',
        transaction_type: transactionTypes[0]?.value ?? 'leave',
        is_active: true,
    });

    function openCreate() {
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        form.post(approvalFlows.store.url(), { onSuccess: () => setOpen(false) });
    }

    function handleDelete(id: number) {
        router.delete(approvalFlows.destroy.url(id), { preserveScroll: true });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Workflow Approval" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Workflow Approval"
                    description="Atur alur persetujuan per jenis transaksi: langkah, penyetuju, dan SLA."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Alur Baru
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama Alur</TableHead>
                                        <TableHead>Jenis Transaksi</TableHead>
                                        <TableHead>Langkah</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <GitBranch className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada alur persetujuan
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
                                                    {label(
                                                        transactionTypes,
                                                        item.transaction_type,
                                                    )}
                                                </TableCell>
                                                <TableCell className="tabular-nums">
                                                    {item.steps_count} langkah
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={
                                                            item.is_active
                                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300'
                                                        }
                                                    >
                                                        {item.is_active ? 'Aktif' : 'Nonaktif'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="outline"
                                                        >
                                                            <Link
                                                                href={approvalFlows.show.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Settings2 />
                                                                Atur
                                                            </Link>
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Alur"
                                                            description={`Yakin hapus alur "${item.name}" beserta langkahnya?`}
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
                                {paginator.total} alur
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
                        <DialogTitle>Alur Persetujuan Baru</DialogTitle>
                    </DialogHeader>

                    <form id="flow-form" onSubmit={handleSubmit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="af-name">Nama Alur</Label>
                            <Input
                                id="af-name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="Mis. Persetujuan Cuti 2 Level"
                            />
                            {form.errors.name && (
                                <p className="text-sm text-destructive">
                                    {form.errors.name}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="af-type">Jenis Transaksi</Label>
                            <Select
                                value={form.data.transaction_type}
                                onValueChange={(value) =>
                                    form.setData('transaction_type', value)
                                }
                            >
                                <SelectTrigger id="af-type" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {transactionTypes.map((option) => (
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
                        <Button type="submit" form="flow-form" disabled={form.processing}>
                            Buat & Atur
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
