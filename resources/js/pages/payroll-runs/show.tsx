import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Banknote,
    Calculator,
    Eye,
    Lock,
    Receipt,
    RotateCcw,
    Shield,
    TrendingUp,
    Users,
    Wallet,
} from 'lucide-react';
import payrollRuns from '@/actions/App/Http/Controllers/PayrollRunController';
import payslips from '@/actions/App/Http/Controllers/PayslipController';
import ConfirmDialog from '@/components/confirm-dialog';
import { InfoHero } from '@/components/detail/info-hero';
import { SectionCard } from '@/components/detail/section-card';
import { StatTile } from '@/components/detail/stat-tile';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

const STATUS_META: Record<string, { label: string; className: string }> = {
    draft: {
        label: 'Draft',
        className:
            'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
    },
    calculated: {
        label: 'Dihitung',
        className:
            'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    },
    approved: {
        label: 'Disetujui & Terkunci',
        className:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    },
    paid: {
        label: 'Dibayar',
        className:
            'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-400',
    },
};

export default function PayrollRunsShow({ run, payslips: rows }: ShowProps) {
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
        router.post(payrollRuns.pay.url(run.id), {}, { preserveScroll: true });
    }

    const processLabel =
        run.status === 'calculated' ? 'Hitung Ulang' : 'Hitung Payroll';
    const status = STATUS_META[run.status] ?? {
        label: run.status,
        className: 'bg-muted text-muted-foreground',
    };

    return (
        <>
            <Head title={`Proses Payroll — ${run.run_no}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={`Proses Payroll — ${run.run_no}`}
                    description={run.period_code ?? undefined}
                >
                    <Button asChild variant="secondary">
                        <Link href={payrollRuns.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    {run.can_process ? (
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
                    ) : null}
                    {run.can_approve ? (
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
                    ) : null}
                    {run.can_revert ? (
                        <ConfirmDialog
                            title="Batalkan Persetujuan"
                            description="Batalkan persetujuan? Payroll kembali ke status calculated dan bisa dihitung ulang."
                            confirmLabel="Batalkan Persetujuan"
                            onConfirm={revert}
                            trigger={
                                <Button variant="secondary">
                                    <RotateCcw />
                                    Batalkan Persetujuan
                                </Button>
                            }
                        />
                    ) : null}
                    {run.can_pay ? (
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
                    ) : null}
                </PageHeader>

                <InfoHero
                    icon={Wallet}
                    title={run.run_no}
                    subtitle={run.period_code ?? 'Tanpa periode'}
                    badges={
                        <Badge variant="secondary" className={status.className}>
                            {status.label}
                        </Badge>
                    }
                    aside={
                        <>
                            <p className="text-xs text-muted-foreground">
                                Total Netto
                            </p>
                            <p className="text-2xl font-semibold text-primary">
                                {formatRupiah(run.net_total)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {rows.length} karyawan
                            </p>
                        </>
                    }
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatTile
                        label="Total Bruto"
                        value={formatRupiah(run.gross_total)}
                        icon={TrendingUp}
                        accent="blue"
                    />
                    <StatTile
                        label="Total BPJS"
                        value={formatRupiah(run.bpjs_total)}
                        icon={Shield}
                        accent="violet"
                    />
                    <StatTile
                        label="Total PPh21"
                        value={formatRupiah(run.tax_total)}
                        icon={Receipt}
                        accent="amber"
                    />
                    <StatTile
                        label="Total Netto"
                        value={formatRupiah(run.net_total)}
                        icon={Banknote}
                        accent="green"
                    />
                </div>

                <SectionCard
                    title="Payslip Karyawan"
                    icon={Users}
                    actions={
                        <Badge
                            variant="secondary"
                            className="bg-muted text-muted-foreground"
                        >
                            {rows.length}
                        </Badge>
                    }
                    contentClassName="px-0"
                >
                    <div className="overflow-x-auto border-t">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12 pl-6">
                                        No
                                    </TableHead>
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
                                    <TableHead className="pr-6 text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="py-12 text-center text-sm text-muted-foreground"
                                        >
                                            Belum ada slip gaji — klik Hitung
                                            Payroll.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    rows.map((row, index) => (
                                        <TableRow key={row.id}>
                                            <TableCell className="pl-6 text-muted-foreground tabular-nums">
                                                {index + 1}
                                            </TableCell>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {row.employee_name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {row.employee_no}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {formatRupiah(row.gross)}
                                            </TableCell>
                                            <TableCell className="text-right text-muted-foreground tabular-nums">
                                                {formatRupiah(row.deductions)}
                                            </TableCell>
                                            <TableCell className="text-right text-muted-foreground tabular-nums">
                                                {formatRupiah(row.tax)}
                                            </TableCell>
                                            <TableCell className="text-right text-muted-foreground tabular-nums">
                                                {formatRupiah(
                                                    row.bpjs_employee,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right font-semibold tabular-nums">
                                                {formatRupiah(row.net)}
                                            </TableCell>
                                            <TableCell className="pr-6 text-right">
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="warning"
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
                </SectionCard>
            </div>
        </>
    );
}
