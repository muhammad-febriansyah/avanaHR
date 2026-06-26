import { Head, Link } from '@inertiajs/react';
import { Pencil, Users as UsersIcon } from 'lucide-react';
import users from '@/actions/App/Http/Controllers/UserController';
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

type UserRow = {
    id: number;
    name: string;
    email: string;
    status: string;
    employee_no: string | null;
    roles: string[];
};

type IndexProps = {
    users: UserRow[];
};

export default function UsersIndex({ users: rows }: IndexProps) {
    useFlashToast();

    return (
        <>
            <Head title="Pengguna" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Pengguna"
                    description="Kelola akun pengguna dan role hak aksesnya."
                />

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <div className="overflow-x-auto rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>NIK</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <UsersIcon className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada pengguna
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
                                                <TableCell className="font-medium whitespace-nowrap">
                                                    {item.name}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {item.email}
                                                </TableCell>
                                                <TableCell className="tabular-nums">
                                                    {item.employee_no ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {item.roles.length === 0 ? (
                                                        <span className="text-xs text-muted-foreground">
                                                            Tanpa role
                                                        </span>
                                                    ) : (
                                                        <div className="flex flex-wrap gap-1">
                                                            {item.roles.map(
                                                                (role) => (
                                                                    <span
                                                                        key={
                                                                            role
                                                                        }
                                                                        className="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                                                    >
                                                                        {role}
                                                                    </span>
                                                                ),
                                                            )}
                                                        </div>
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
                                                                href={users.edit.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Atur Role
                                                            </Link>
                                                        </Button>
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
