import { Head, Link, router } from '@inertiajs/react';
import { Coins, Pencil, Plus, Trash2 } from 'lucide-react';
import costCenters from '@/actions/App/Http/Controllers/CostCenterController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
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

type CostCenterRow = {
    id: number;
    code: string;
    name: string;
};

type IndexProps = {
    costCenters: CostCenterRow[];
};

export default function CostCentersIndex({ costCenters: rows }: IndexProps) {
    useFlashToast();

    function handleDelete(id: number) {
        router.delete(costCenters.destroy.url(id), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Cost Center" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Cost Center"
                    description="Kelola pusat biaya untuk alokasi anggaran."
                >
                    <Button asChild>
                        <Link href={costCenters.create.url()}>
                            <Plus />
                            Tambah Cost Center
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <div className="overflow-x-auto rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={3}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <Coins className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada cost center
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="font-medium whitespace-nowrap">
                                                    {item.code}
                                                </TableCell>
                                                <TableCell>
                                                    {item.name}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="outline"
                                                        >
                                                            <Link
                                                                href={costCenters.edit.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <ConfirmDialog
                                                            title="Hapus Cost Center"
                                                            description={`Yakin ingin menghapus "${item.name}"? Tindakan ini tidak dapat dibatalkan.`}
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
