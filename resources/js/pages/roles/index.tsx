import { Head, Link, router } from '@inertiajs/react';
import { Lock, Pencil, Plus, ShieldCheck, Trash2 } from 'lucide-react';
import roles from '@/actions/App/Http/Controllers/RoleController';
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

type RoleRow = {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
    is_system: boolean;
};

type IndexProps = {
    roles: RoleRow[];
};

export default function RolesIndex({ roles: rows }: IndexProps) {
    useFlashToast();

    function handleDelete(id: number) {
        router.delete(roles.destroy.url(id), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Hak Akses (Role)" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Hak Akses (Role)"
                    description="Kelola role dan hak akses pengguna pada tenant Anda."
                >
                    <Button asChild>
                        <Link href={roles.create.url()}>
                            <Plus />
                            Tambah Role
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <div className="overflow-x-auto rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Role</TableHead>
                                        <TableHead className="text-right">
                                            Hak Akses
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Pengguna
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
                                                colSpan={4}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <ShieldCheck className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada role
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="font-medium">
                                                    <span className="inline-flex items-center gap-2">
                                                        {item.name}
                                                        {item.is_system && (
                                                            <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                                                <Lock className="size-3" />
                                                                Sistem
                                                            </span>
                                                        )}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {item.permissions_count}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {item.users_count}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="outline"
                                                        >
                                                            <Link
                                                                href={roles.edit.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        {!item.is_system && (
                                                            <ConfirmDialog
                                                                title="Hapus Role"
                                                                description={`Yakin ingin menghapus role "${item.name}"? Tindakan ini tidak dapat dibatalkan.`}
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
                                                        )}
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
