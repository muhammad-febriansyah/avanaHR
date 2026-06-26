import { Head, Link, router } from '@inertiajs/react';
import { Clock, Moon, Pencil, Plus, Trash2 } from 'lucide-react';
import shifts from '@/actions/App/Http/Controllers/ShiftController';
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

type Shift = {
    id: number;
    code: string;
    name: string;
    start_time: string;
    end_time: string;
    break_min: number;
    is_overnight: boolean;
    late_tolerance_min: number;
    grace_min: number;
};

type IndexProps = { shifts: Shift[] };

export default function ShiftsIndex({ shifts: rows }: IndexProps) {
    useFlashToast();

    function handleDelete(id: number) {
        router.delete(shifts.destroy.url(id), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Jadwal & Shift" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Jadwal & Shift"
                    description="Kelola pola jam kerja dan toleransi keterlambatan."
                >
                    <Button asChild>
                        <Link href={shifts.create.url()}>
                            <Plus />
                            Tambah Shift
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Jam Kerja</TableHead>
                                        <TableHead className="text-right">
                                            Istirahat
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Toleransi
                                        </TableHead>
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
                                                        <Clock className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada shift
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item, index) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="text-muted-foreground tabular-nums">
                                                    {index + 1}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {item.code}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        {item.name}
                                                        {item.is_overnight && (
                                                            <Badge
                                                                variant="secondary"
                                                                className="gap-1"
                                                            >
                                                                <Moon className="size-3" />
                                                                Lintas hari
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {item.start_time} –{' '}
                                                    {item.end_time}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.break_min} mnt
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.late_tolerance_min}{' '}
                                                    mnt
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="success"
                                                        >
                                                            <Link
                                                                href={shifts.edit.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Shift"
                                                            description={`Yakin ingin menghapus "${item.name}"?`}
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
        </>
    );
}
