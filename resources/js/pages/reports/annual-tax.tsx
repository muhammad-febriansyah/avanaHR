import { Head, router } from '@inertiajs/react';
import { FileText, Printer, Receipt, Scale, Users } from 'lucide-react';
import form1721 from '@/actions/App/Http/Controllers/Form1721A1Controller';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatRupiah } from '@/lib/format';

type Row = {
    employee_name: string | null;
    employee_no: string | null;
    npwp: string | null;
    ptkp_status: string | null;
    gross: number;
    occupational_cost: number;
    ptkp: number;
    pkp: number;
    annual_tax: number;
    withheld: number;
    difference: number;
};

type IndexProps = {
    year: number;
    years: number[];
    rows: Row[];
    summary: {
        employees: number;
        annual_tax: number;
        withheld: number;
        difference: number;
    };
};

export default function AnnualTaxReport({
    year,
    years,
    rows,
    summary,
}: IndexProps) {
    function setYear(value: string) {
        router.get(
            form1721.index.url(),
            { year: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const cards = [
        { label: 'Karyawan', value: String(summary.employees), icon: Users },
        {
            label: 'PPh 21 Terutang',
            value: formatRupiah(summary.annual_tax),
            icon: Receipt,
        },
        {
            label: 'PPh 21 Dipotong',
            value: formatRupiah(summary.withheld),
            icon: FileText,
        },
        {
            label: summary.difference >= 0 ? 'Kurang Bayar' : 'Lebih Bayar',
            value: formatRupiah(Math.abs(summary.difference)),
            icon: Scale,
        },
    ];

    return (
        <>
            <Head title="Bukti Potong 1721-A1" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Bukti Potong 1721-A1"
                    description="Rekonsiliasi PPh 21 tahunan per karyawan. Angka tarif placeholder — validasi akuntan."
                >
                    <Select value={String(year)} onValueChange={setYear}>
                        <SelectTrigger className="w-32">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {years.map((y) => (
                                <SelectItem key={y} value={String(y)}>
                                    {y}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button
                        variant="outline"
                        onClick={() =>
                            window.open(
                                form1721.print.url({ query: { year } }),
                                '_blank',
                            )
                        }
                    >
                        <Printer />
                        Cetak PDF
                    </Button>
                </PageHeader>

                <div className="grid gap-4 md:grid-cols-4">
                    {cards.map((card) => (
                        <Card key={card.label} className="py-0">
                            <CardContent className="flex items-center gap-3 p-4">
                                <div className="rounded-lg bg-muted p-2">
                                    <card.icon className="size-5 text-primary" />
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        {card.label}
                                    </p>
                                    <p className="text-lg font-semibold text-navy">
                                        {card.value}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card className="gap-0 py-0">
                    <CardContent className="p-5">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Karyawan</TableHead>
                                    <TableHead>NPWP</TableHead>
                                    <TableHead>PTKP</TableHead>
                                    <TableHead className="text-right">
                                        Bruto Setahun
                                    </TableHead>
                                    <TableHead className="text-right">
                                        PKP
                                    </TableHead>
                                    <TableHead className="text-right">
                                        PPh 21 Terutang
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Dipotong
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Kurang/Lebih
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada data payroll untuk tahun
                                            ini
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    rows.map((row) => (
                                        <TableRow key={row.employee_no}>
                                            <TableCell className="font-medium text-navy">
                                                {row.employee_name}
                                                <span className="block text-xs text-muted-foreground">
                                                    {row.employee_no}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                {row.npwp ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {row.ptkp_status ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatRupiah(row.gross)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatRupiah(row.pkp)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatRupiah(row.annual_tax)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatRupiah(row.withheld)}
                                            </TableCell>
                                            <TableCell
                                                className={`text-right font-medium ${row.difference > 0 ? 'text-destructive' : 'text-success'}`}
                                            >
                                                {formatRupiah(
                                                    Math.abs(row.difference),
                                                )}
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
