import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import payslips from '@/actions/App/Http/Controllers/PayslipController';
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

const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
});

function SummaryRow({ label, value }: { label: string; value: number }) {
    return (
        <div className="flex items-center justify-between py-1 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium">{rupiah.format(value)}</span>
        </div>
    );
}

export default function PayslipShow({ payslip }: ShowProps) {
    const earnings = payslip.lines.filter((line) => line.type === 'earning');
    const deductions = payslip.lines.filter(
        (line) => line.type === 'deduction',
    );

    return (
        <>
            <Head title={`Slip Gaji — ${payslip.employee_name ?? ''}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Slip Gaji"
                    description={`${payslip.run_no ?? '-'} · ${payslip.period_code ?? '-'}`}
                >
                    <Button variant="outline" asChild>
                        <Link href={payslips.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="grid gap-1 p-5 sm:grid-cols-2">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Karyawan
                            </p>
                            <p className="font-medium">
                                {payslip.employee_name}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {payslip.employee_no}
                            </p>
                        </div>
                        <div className="sm:text-right">
                            <p className="text-xs text-muted-foreground">
                                Take Home Pay
                            </p>
                            <p className="text-xl font-semibold text-primary">
                                {rupiah.format(payslip.net)}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-5 lg:grid-cols-2">
                    <Card className="gap-0 py-0">
                        <CardContent className="p-5">
                            <h2 className="mb-3 text-sm font-semibold">
                                Pendapatan
                            </h2>
                            <LineTable lines={earnings} emptyText="Tidak ada pendapatan" />
                        </CardContent>
                    </Card>
                    <Card className="gap-0 py-0">
                        <CardContent className="p-5">
                            <h2 className="mb-3 text-sm font-semibold">
                                Potongan
                            </h2>
                            <LineTable lines={deductions} emptyText="Tidak ada potongan" />
                        </CardContent>
                    </Card>
                </div>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <h2 className="mb-3 text-sm font-semibold">Ringkasan</h2>
                        <SummaryRow label="Gross" value={payslip.gross} />
                        <SummaryRow
                            label="Total Potongan"
                            value={payslip.deductions}
                        />
                        <SummaryRow label="PPh 21" value={payslip.tax} />
                        <SummaryRow
                            label="BPJS (Karyawan)"
                            value={payslip.bpjs_employee}
                        />
                        <SummaryRow
                            label="BPJS (Perusahaan)"
                            value={payslip.bpjs_company}
                        />
                        <div className="mt-2 flex items-center justify-between border-t pt-2 text-sm">
                            <span className="font-semibold">Net</span>
                            <span className="font-semibold text-primary">
                                {rupiah.format(payslip.net)}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function LineTable({
    lines,
    emptyText,
}: {
    lines: Line[];
    emptyText: string;
}) {
    if (lines.length === 0) {
        return (
            <p className="py-6 text-center text-sm text-muted-foreground">
                {emptyText}
            </p>
        );
    }

    return (
        <div className="rounded-lg border">
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
                            <TableCell className="text-right">
                                {rupiah.format(line.amount)}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
