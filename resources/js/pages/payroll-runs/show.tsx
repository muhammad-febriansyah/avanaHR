import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Banknote, Calculator, Eye, Lock, RotateCcw } from 'lucide-react';
import payslips from '@/actions/App/Http/Controllers/PayslipController';
import payrollRuns from '@/actions/App/Http/Controllers/PayrollRunController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatRupiah } from '@/lib/format';

type Run = {
    id: number;
    run_no: string;
    status: string;
    period_code: string | null;
    gross_total: number;
    net_total: number;
    tax_total: number;
    bpjs_total: number;
    can_process: boolean;
    can_approve: boolean;
    can_revert: boolean;
    can_pay: boolean;
};

type Payslip = {
    id: number;
    employee_name: string;
    employee_no: string;
    gross: number;
    deductions: number;
    tax: number;
    bpjs_employee: number;
    net: number;
};

type ShowProps = {
    run: Run;
    payslips: Payslip[];
};

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
    calculated: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
};

type SummaryCard = { label: string; value: number };

export default function PayrollRunsShow({
    run,
    payslips: rows,
}: ShowProps) {
    useFlashToast();

    function process() {
        router.post(
            payrollRuns.process.url(run.id),
            {},
            { preserveScroll: true },
        );
    }

    function approve() {
        router.post(
            payrollRuns.approve.url(run.id),
            {},
            { preserveScroll: true },
        );
    }

    function revert() {
        router.post(
            payrollRuns.revert.url(run.id),
            {},
            { preserveScroll: true },
        );
    }

    function pay() {
        router.post(
            payrollRuns.pay.url(run.id),
            {},
            { preserveScroll: true },
        );
    }

    const processLabel = run.status === 'calculated' ? 'Hitung Ulang' : 'Hitung Payroll';

    const summary: SummaryCard[] = [
        { label: 'Total Bruto', value: run.gross_total },
        { label: 'Total BPJS', value: run.bpjs_total },
        { label: 'Total PPh21', value: run.tax_total },
        { label: 'Total Netto', value: run.net_total },
    ];

    return (
        <>
            <Head title={`Proses Payroll — ${run.run_no}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={`Proses Payroll — ${run.run_no}`}
                    description={run.period_code ?? undefined}
                >
                    <Button asChild variant="outline">
                        <Link href={payrollRuns.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    {run.can_process && (
                        <ConfirmDialog
                            title={processLabel}
                            description="Hitung payroll untuk periode ini? Payslip akan dibuat/diperbarui."
                            confirmLabel={processLabel}
                            onConfirm={process}
                            trigger={
                                <Button>
                                    <Calculator />
                                    {processLabel}
                                </Button>
                            }
                        />
                    )}
                    {run.can_approve && (
                        <ConfirmDialog
                            title="Setujui & Kunci"
                            description="Setujui & kunci payroll ini? Setelah disetujui payroll tidak bisa dihitung ulang."
                            confirmLabel="Setujui & Kunci"
                            onConfirm={approve}
                            trigger={
                                <Button className="bg-emerald-600 text-white hover:bg-emerald-700">
                                    <Lock />
                                    Setujui & Kunci
                                </Button>
                            }
                        />
                    )}
                    {run.can_revert && (
                        <ConfirmDialog
                            title="Batalkan Persetujuan"
                            description="Batalkan persetujuan? Payroll kembali ke status calculated dan bisa dihitung ulang."
                            confirmLabel="Batalkan Persetujuan"
                            onConfirm={revert}
                            trigger={
                                <Button variant="outline">
                                    <RotateCcw />
                                    Batalkan Persetujuan
                                </Button>
                            }
                        />
                    )}
                    {run.can_pay && (
                        <ConfirmDialog
                            title="Tandai Dibayar"
                            description="Tandai payroll sudah dibayar? Status menjadi final."
                            confirmLabel="Tandai Dibayar"
                            onConfirm={pay}
                            trigger={
                                <Button className="bg-teal-600 text-white hover:bg-teal-700">
                                    <Banknote />
                                    Tandai Dibayar
                                </Button>
                            }
                        />
                    )}
                </PageHeader>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {summary.map((card) => (
                        <Card key={card.label}>
                            <CardContent className="flex flex-col gap-1 p-5">
                                <span className="text-xs text-muted-foreground">
                                    {card.label}
                                </span>
                                <span className="text-lg font-semibold text-navy">
                                    {formatRupiah(card.value)}
                                </span>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="text-base text-navy">
                            Payslip
                        </CardTitle>
                        <Badge
                            variant="secondary"
                            className={STATUS_STYLES[run.status]}
                        >
                            {run.status}
                        </Badge>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead className="text-right">
                                            Bruto
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Potongan
                                        </TableHead>
                                        <TableHead className="text-right">
                                            PPh21
                                        </TableHead>
                                        <TableHead className="text-right">
                                            BPJS
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Netto
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
                                                className="py-12 text-center text-sm text-muted-foreground"
                                            >
                                                Belum ada payslip — klik Hitung
                                                Payroll.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {row.employee_name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {row.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatRupiah(row.gross)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatRupiah(
                                                        row.deductions,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatRupiah(row.tax)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatRupiah(
                                                        row.bpjs_employee,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right font-semibold">
                                                    {formatRupiah(row.net)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <Link
                                                            href={payslips.show.url(
                                                                row.id,
                                                            )}
                                                        >
                                                            <Eye />
                                                            Lihat
                                                        </Link>
                                                    </Button>
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
