import { Head, Link, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import bpjsParameters from '@/actions/App/Http/Controllers/BpjsParameterController';
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
import { formatDateID, formatRupiah } from '@/lib/format';

type TkRates = {
    jht_employee?: number;
    jht_employer?: number;
    jkk?: number;
    jkm?: number;
    jp_employee?: number;
    jp_employer?: number;
    jp_cap?: number;
};

type Parameter = {
    id: number;
    effective_date: string | null;
    kes_rate_employee: number;
    kes_rate_employer: number;
    kes_cap: number;
    tk_rates: TkRates;
};

type IndexProps = { parameters: Parameter[] };

export default function BpjsParametersIndex({ parameters }: IndexProps) {
    useFlashToast();

    return (
        <>
            <Head title="Parameter BPJS" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Parameter BPJS"
                    description="Iuran & plafon BPJS per tenant, berlaku per tanggal efektif."
                >
                    <Button asChild>
                        <Link href={bpjsParameters.create.url()}>
                            <Plus />
                            Tambah Parameter
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12">No</TableHead>
                                    <TableHead>Berlaku</TableHead>
                                    <TableHead>Kes (Kary/Persh)</TableHead>
                                    <TableHead>JHT (Kary/Persh)</TableHead>
                                    <TableHead>JP (Kary/Persh)</TableHead>
                                    <TableHead>Plafon Kes</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {parameters.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada parameter BPJS.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    parameters.map((p, index) => (
                                        <TableRow key={p.id}>
                                            <TableCell className="text-muted-foreground tabular-nums">
                                                {index + 1}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {formatDateID(p.effective_date)}
                                            </TableCell>
                                            <TableCell>
                                                {p.kes_rate_employee}% /{' '}
                                                {p.kes_rate_employer}%
                                            </TableCell>
                                            <TableCell>
                                                {p.tk_rates.jht_employee ?? 0}%
                                                / {p.tk_rates.jht_employer ?? 0}
                                                %
                                            </TableCell>
                                            <TableCell>
                                                {p.tk_rates.jp_employee ?? 0}% /{' '}
                                                {p.tk_rates.jp_employer ?? 0}%
                                            </TableCell>
                                            <TableCell>
                                                {formatRupiah(p.kes_cap)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ConfirmDialog
                                                    title="Hapus Parameter BPJS"
                                                    description={`Hapus parameter berlaku ${p.effective_date}?`}
                                                    confirmLabel="Hapus"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            bpjsParameters.destroy.url(
                                                                p.id,
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
