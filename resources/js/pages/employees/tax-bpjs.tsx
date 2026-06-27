import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import employees from '@/actions/App/Http/Controllers/EmployeeController';
import taxBpjs from '@/actions/App/Http/Controllers/EmployeeTaxBpjsController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatDateID, formatRupiah } from '@/lib/format';

type TaxProfile = {
    id: number;
    effective_date: string | null;
    ptkp_status: string | null;
    npwp: string | null;
    tax_method: string;
    beginning_ytd: number;
};

type Flags = Record<string, boolean>;

type BpjsProfile = {
    id: number;
    effective_date: string | null;
    bpjs_kesehatan_no: string | null;
    bpjs_tk_no: string | null;
    kesehatan_basis: number;
    tk_basis: number;
    participation_flags: Flags;
};

type Props = {
    employee: { id: number; name: string; employee_no: string };
    taxProfiles: TaxProfile[];
    bpjsProfiles: BpjsProfile[];
    ptkpOptions: string[];
};

const PARTICIPATION = ['kesehatan', 'jht', 'jkk', 'jkm', 'jp'];

export default function EmployeeTaxBpjs({
    employee,
    taxProfiles,
    bpjsProfiles,
}: Props) {
    useFlashToast();

    return (
        <>
            <Head title={`Pajak & BPJS — ${employee.name}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={`Pajak & BPJS — ${employee.name}`}
                    description="Profil PTKP/pajak dan BPJS karyawan (per tanggal efektif)."
                >
                    <Button asChild variant="secondary">
                        <Link href={employees.show.url(employee.id)}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {/* Tax profiles */}
                <Card className="gap-0 py-0">
                    <CardHeader className="flex-row items-center justify-between px-5 pt-5">
                        <CardTitle>Profil Pajak (PTKP)</CardTitle>
                        <Button asChild size="sm">
                            <Link href={taxBpjs.createTax.url(employee.id)}>
                                <Plus />
                                Tambah
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0 pt-3">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12">No</TableHead>
                                    <TableHead>Berlaku</TableHead>
                                    <TableHead>PTKP</TableHead>
                                    <TableHead>NPWP</TableHead>
                                    <TableHead>Metode</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {taxProfiles.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Belum ada profil pajak.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    taxProfiles.map((tp, index) => (
                                        <TableRow key={tp.id}>
                                            <TableCell className="text-muted-foreground tabular-nums">
                                                {index + 1}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {formatDateID(tp.effective_date)}
                                            </TableCell>
                                            <TableCell>
                                                {tp.ptkp_status}
                                            </TableCell>
                                            <TableCell>
                                                {tp.npwp ?? '-'}
                                            </TableCell>
                                            <TableCell className="uppercase">
                                                {tp.tax_method}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ConfirmDialog
                                                    title="Hapus Profil Pajak"
                                                    description={`Hapus profil pajak berlaku ${tp.effective_date}?`}
                                                    confirmLabel="Hapus"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            taxBpjs.destroyTax.url(
                                                                tp.id,
                                                            ),
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            size="sm"
                                                            variant="destructive"
                                                        >
                                                            <Trash2 />
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

                {/* BPJS profiles */}
                <Card className="gap-0 py-0">
                    <CardHeader className="flex-row items-center justify-between px-5 pt-5">
                        <CardTitle>Profil BPJS</CardTitle>
                        <Button asChild size="sm">
                            <Link href={taxBpjs.createBpjs.url(employee.id)}>
                                <Plus />
                                Tambah
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0 pt-3">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12">No</TableHead>
                                    <TableHead>Berlaku</TableHead>
                                    <TableHead>Basis Kes</TableHead>
                                    <TableHead>Basis TK</TableHead>
                                    <TableHead>Ikut</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {bpjsProfiles.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Belum ada profil BPJS.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    bpjsProfiles.map((bp, index) => (
                                        <TableRow key={bp.id}>
                                            <TableCell className="text-muted-foreground tabular-nums">
                                                {index + 1}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {formatDateID(bp.effective_date)}
                                            </TableCell>
                                            <TableCell>
                                                {formatRupiah(
                                                    bp.kesehatan_basis,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatRupiah(bp.tk_basis)}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {PARTICIPATION.filter(
                                                        (k) =>
                                                            bp
                                                                .participation_flags[
                                                                k
                                                            ],
                                                    ).map((k) => (
                                                        <Badge
                                                            key={k}
                                                            variant="secondary"
                                                            className="uppercase"
                                                        >
                                                            {k}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ConfirmDialog
                                                    title="Hapus Profil BPJS"
                                                    description={`Hapus profil BPJS berlaku ${bp.effective_date}?`}
                                                    confirmLabel="Hapus"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            taxBpjs.destroyBpjs.url(
                                                                bp.id,
                                                            ),
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            size="sm"
                                                            variant="destructive"
                                                        >
                                                            <Trash2 />
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
