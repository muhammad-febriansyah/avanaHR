import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import approvalDelegations from '@/actions/App/Http/Controllers/ApprovalDelegationController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
type UserOption = { id: number; label: string };

type Delegation = {
    id: number;
    to_user: string | null;
    transaction_types: string[] | null;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
};

type IndexProps = {
    delegations: Delegation[];
    users: UserOption[];
    transactionTypes: Option[];
};

export default function ApprovalDelegationsIndex({
    delegations,
    users,
    transactionTypes,
}: IndexProps) {
    useFlashToast();

    const form = useForm({
        to_user_id: '',
        transaction_type: 'all',
        starts_at: '',
        ends_at: '',
    });

    function typeLabel(types: string[] | null): string {
        if (!types || types.length === 0) {
            return 'Semua Transaksi';
        }

        return types
            .map(
                (t) =>
                    transactionTypes.find((opt) => opt.value === t)?.label ?? t,
            )
            .join(', ');
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(approvalDelegations.store.url(), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function remove(id: number) {
        form.delete(approvalDelegations.destroy.url(id), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Delegasi Approval" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Delegasi Approval"
                    description="Alihkan tugas persetujuan Anda ke rekan lain untuk periode tertentu."
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Tambah Delegasi</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid gap-4 md:grid-cols-2 lg:grid-cols-4"
                        >
                            <div className="space-y-1">
                                <Label>
                                    Delegasikan ke{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={form.data.to_user_id}
                                    onValueChange={(v) =>
                                        form.setData('to_user_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih pengguna" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.map((u) => (
                                            <SelectItem
                                                key={u.id}
                                                value={String(u.id)}
                                            >
                                                {u.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.to_user_id ? (
                                    <p className="text-sm text-red-600">
                                        {form.errors.to_user_id}
                                    </p>
                                ) : null}
                            </div>

                            <div className="space-y-1">
                                <Label>Jenis Transaksi</Label>
                                <Select
                                    value={form.data.transaction_type}
                                    onValueChange={(v) =>
                                        form.setData('transaction_type', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {transactionTypes.map((opt) => (
                                            <SelectItem
                                                key={opt.value}
                                                value={opt.value}
                                            >
                                                {opt.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label>Mulai</Label>
                                <Input
                                    type="date"
                                    value={form.data.starts_at}
                                    onChange={(e) =>
                                        form.setData('starts_at', e.target.value)
                                    }
                                />
                            </div>

                            <div className="space-y-1">
                                <Label>Selesai</Label>
                                <Input
                                    type="date"
                                    value={form.data.ends_at}
                                    onChange={(e) =>
                                        form.setData('ends_at', e.target.value)
                                    }
                                />
                                {form.errors.ends_at ? (
                                    <p className="text-sm text-red-600">
                                        {form.errors.ends_at}
                                    </p>
                                ) : null}
                            </div>

                            <div className="md:col-span-2 lg:col-span-4">
                                <Button type="submit" disabled={form.processing}>
                                    <Plus />
                                    Tambah Delegasi
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card className="gap-0 py-0">
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Delegasikan ke</TableHead>
                                    <TableHead>Jenis</TableHead>
                                    <TableHead>Periode</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {delegations.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada delegasi.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    delegations.map((d) => (
                                        <TableRow key={d.id}>
                                            <TableCell className="font-medium">
                                                {d.to_user ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {typeLabel(d.transaction_types)}
                                            </TableCell>
                                            <TableCell>
                                                {d.starts_at ?? '—'} s/d{' '}
                                                {d.ends_at ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="secondary"
                                                    className={
                                                        d.is_active
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                            : 'bg-muted text-muted-foreground'
                                                    }
                                                >
                                                    {d.is_active
                                                        ? 'Aktif'
                                                        : 'Nonaktif'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ConfirmDialog
                                                    title="Hapus Delegasi"
                                                    description={`Hapus delegasi ke "${d.to_user}"?`}
                                                    confirmLabel="Hapus"
                                                    onConfirm={() => remove(d.id)}
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
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
