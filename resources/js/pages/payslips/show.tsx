import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Banknote,
    Minus,
    Plus,
    Printer,
    Receipt,
    Shield,
    TrendingUp,
} from 'lucide-react';
import payslips from '@/actions/App/Http/Controllers/PayslipController';
import { InfoHero } from '@/components/detail/info-hero';
import { SectionCard } from '@/components/detail/section-card';
import { StatTile } from '@/components/detail/stat-tile';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatRupiah } from '@/lib/format';

type Line = {
    id: number;
    component_code: string;
    component_name: string;
    type: string;
    amount: number;
};

type Payslip = {
    id: number;
    employee_name: string | null;
    employee_no: string | null;
    run_no: string | null;
    period_code: string | null;
    gross: number;
    deductions: number;
    tax: number;
    bpjs_employee: number;
    bpjs_company: number;
    net: number;
    lines: Line[];
};

type ShowProps = { payslip: Payslip };

function SummaryRow({
    label,
    value,
    sign,
}: {
    label: string;
    value: number;
    sign?: '+' | '−';
}) {
    return (
        <div className="flex items-center justify-between py-1.5 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium tabular-nums">
                {sign === '−' && value !== 0 ? '− ' : ''}
                {formatRupiah(value)}
            </span>
        </div>
    );
}

export default function PayslipShow({ payslip }: ShowProps) {
    const earnings = payslip.lines.filter((line) => line.type === 'earning');
    const deductions = payslip.lines.filter(
        (line) => line.type === 'deduction',
    );

    const initials = (payslip.employee_name ?? '?')
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <>
            <Head title={`Slip Gaji — ${payslip.employee_name ?? ''}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Slip Gaji"
                    description={`${payslip.run_no ?? '-'} · ${payslip.period_code ?? '-'}`}
                >
                    <Button variant="secondary" asChild>
                        <Link href={payslips.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    <Button asChild>
                        <a
                            href={`/payslips/${payslip.id}/print`}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Printer />
                            Cetak / PDF
                        </a>
                    </Button>
                </PageHeader>

                <InfoHero
                    initials={initials}
                    title={payslip.employee_name ?? '—'}
                    subtitle={`${payslip.employee_no ?? '-'} · ${payslip.run_no ?? '-'} · ${payslip.period_code ?? '-'}`}
                    aside={
                        <>
                            <p className="text-xs text-muted-foreground">
                                Take Home Pay
                            </p>
                            <p className="text-2xl font-semibold text-primary">
                                {formatRupiah(payslip.net)}
                            </p>
                        </>
                    }
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatTile
                        label="Pendapatan Bruto"
                        value={formatRupiah(payslip.gross)}
                        icon={TrendingUp}
                        accent="green"
                    />
                    <StatTile
                        label="Total Potongan"
                        value={formatRupiah(payslip.deductions)}
                        icon={Minus}
                        accent="amber"
                    />
                    <StatTile
                        label="PPh 21"
                        value={formatRupiah(payslip.tax)}
                        icon={Receipt}
                        accent="red"
                    />
                    <StatTile
                        label="BPJS (Karyawan)"
                        value={formatRupiah(payslip.bpjs_employee)}
                        icon={Shield}
                        accent="blue"
                    />
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <SectionCard
                        title="Pendapatan"
                        icon={Plus}
                        actions={
                            <span className="text-sm font-semibold text-emerald-600 tabular-nums dark:text-emerald-400">
                                {formatRupiah(payslip.gross)}
                            </span>
                        }
                    >
                        <LineTable
                            lines={earnings}
                            emptyText="Tidak ada pendapatan"
                        />
                    </SectionCard>
                    <SectionCard
                        title="Potongan"
                        icon={Minus}
                        actions={
                            <span className="text-sm font-semibold text-red-600 tabular-nums dark:text-red-400">
                                {formatRupiah(payslip.deductions)}
                            </span>
                        }
                    >
                        <LineTable
                            lines={deductions}
                            emptyText="Tidak ada potongan"
                        />
                    </SectionCard>
                </div>

                <SectionCard title="Ringkasan" icon={Banknote}>
                    <SummaryRow
                        label="Pendapatan Bruto"
                        value={payslip.gross}
                    />
                    <SummaryRow
                        label="Total Potongan"
                        value={payslip.deductions}
                        sign="−"
                    />
                    <SummaryRow label="PPh 21" value={payslip.tax} sign="−" />
                    <SummaryRow
                        label="BPJS (Karyawan)"
                        value={payslip.bpjs_employee}
                        sign="−"
                    />
                    <SummaryRow
                        label="BPJS Ditanggung Perusahaan"
                        value={payslip.bpjs_company}
                    />
                    <div className="mt-2 flex items-center justify-between border-t pt-3">
                        <span className="text-sm font-semibold">
                            Take Home Pay
                        </span>
                        <span className="text-lg font-semibold text-primary tabular-nums">
                            {formatRupiah(payslip.net)}
                        </span>
                    </div>
                </SectionCard>
            </div>
        </>
    );
}

function LineTable({ lines, emptyText }: { lines: Line[]; emptyText: string }) {
    if (lines.length === 0) {
        return (
            <p className="py-6 text-center text-sm text-muted-foreground">
                {emptyText}
            </p>
        );
    }

    return (
        <div className="overflow-hidden rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Komponen</TableHead>
                        <TableHead className="text-right">Nominal</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {lines.map((line) => (
                        <TableRow key={line.id}>
                            <TableCell>
                                <div className="font-medium">
                                    {line.component_name}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    {line.component_code}
                                </div>
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {formatRupiah(line.amount)}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
