import { Head, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    DatabaseBackup,
    Plus,
    RotateCcw,
} from 'lucide-react';
import backups from '@/actions/App/Http/Controllers/Platform/BackupController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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

type BackupRow = {
    id: number;
    type: string;
    status: string;
    location: string | null;
    tenant_name: string | null;
    created_at: string | null;
};

type IndexProps = {
    backups: Paginator<BackupRow>;
};

const STATUS_STYLES: Record<string, string> = {
    completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    running: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    failed: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

const STATUS_LABELS: Record<string, string> = {
    completed: 'Selesai',
    running: 'Berjalan',
    pending: 'Menunggu',
    failed: 'Gagal',
};

const TYPE_LABELS: Record<string, string> = {
    full: 'Penuh',
    database: 'Database',
    files: 'File',
};

export default function BackupsIndex({ backups: paginator }: IndexProps) {
    useFlashToast();

    function createBackup() {
        router.post(backups.store.url(), {}, { preserveScroll: true });
    }

    function restore(id: number) {
        router.post(backups.restore.url(id), {}, { preserveScroll: true });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Backup & Restore" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Backup & Restore"
                    description="Cadangan data platform & pemulihan. Backup berjalan sebagai job terjadwal."
                >
                    <Button onClick={createBackup}>
                        <Plus />
                        Buat Backup
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Lokasi</TableHead>
                                        <TableHead>Cakupan</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <DatabaseBackup className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada backup
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="whitespace-nowrap text-xs text-muted-foreground tabular-nums">
                                                    {row.created_at}
                                                </TableCell>
                                                <TableCell>
                                                    {TYPE_LABELS[row.type] ?? row.type}
                                                </TableCell>
                                                <TableCell className="max-w-[16rem] truncate font-mono text-xs text-muted-foreground">
                                                    {row.location ?? '-'}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {row.tenant_name ?? 'Semua tenant'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={STATUS_STYLES[row.status]}
                                                    >
                                                        {STATUS_LABELS[row.status] ??
                                                            row.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {row.status === 'completed' ? (
                                                        <ConfirmDialog
                                                            title="Pulihkan Backup"
                                                            description={`Yakin pulihkan data dari backup #${row.id}? Tindakan ini menimpa data saat ini.`}
                                                            confirmLabel="Pulihkan"
                                                            onConfirm={() => restore(row.id)}
                                                            trigger={
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                >
                                                                    <RotateCcw />
                                                                    Restore
                                                                </Button>
                                                            }
                                                        />
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">
                                                            -
                                                        </span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} backup
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
        </>
    );
}
