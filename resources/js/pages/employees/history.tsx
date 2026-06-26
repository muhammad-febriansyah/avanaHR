import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CalendarClock } from 'lucide-react';
import { useState } from 'react';
import employees from '@/actions/App/Http/Controllers/EmployeeController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatRupiah } from '@/lib/format';

type Employment = {
    id: number;
    effective_date: string | null;
    end_date: string | null;
    company: string | null;
    branch: string | null;
    department: string | null;
    position: string | null;
    job_grade: string | null;
    cost_center: string | null;
    manager: string | null;
    employment_type: string;
    status: string;
    changes: string[];
};

type SalaryRow = {
    id: number;
    component_name: string | null;
    type: string | null;
    calc_type: string | null;
    amount: number;
    rate: number;
    effective_date: string | null;
};

type HistoryProps = {
    employee: { id: number; name: string; employee_no: string };
    employments: Employment[];
    salary: SalaryRow[];
};

function todayISO(): string {
    return new Date().toISOString().slice(0, 10);
}

function isActiveOn(emp: Employment, asOf: string): boolean {
    if (!emp.effective_date || emp.effective_date > asOf) {
        return false;
    }
    return !emp.end_date || emp.end_date >= asOf;
}

export default function EmployeeHistory({
    employee,
    employments,
    salary,
}: HistoryProps) {
    const [asOf, setAsOf] = useState(todayISO());

    return (
        <>
            <Head title={`Riwayat — ${employee.name}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={`Riwayat — ${employee.name}`}
                    description="Riwayat jabatan, grade, dan gaji berdasarkan tanggal efektif."
                >
                    <Button asChild variant="outline">
                        <Link href={employees.show.url(employee.id)}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <Card>
                    <CardHeader className="flex-row items-center justify-between gap-4">
                        <CardTitle>Riwayat Penempatan</CardTitle>
                        <div className="flex items-center gap-2">
                            <CalendarClock className="size-4 text-muted-foreground" />
                            <Label
                                htmlFor="asof"
                                className="text-sm text-muted-foreground"
                            >
                                Per tanggal
                            </Label>
                            <Input
                                id="asof"
                                type="date"
                                value={asOf}
                                onChange={(e) => setAsOf(e.target.value)}
                                className="w-40"
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {employments.length === 0 ? (
                            <p className="py-8 text-center text-muted-foreground">
                                Belum ada riwayat penempatan.
                            </p>
                        ) : (
                            employments.map((emp) => {
                                const active = isActiveOn(emp, asOf);
                                return (
                                    <div
                                        key={emp.id}
                                        className={`rounded-lg border p-4 ${
                                            active
                                                ? 'border-primary bg-primary/5'
                                                : ''
                                        }`}
                                    >
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <div className="font-medium">
                                                {emp.effective_date} —{' '}
                                                {emp.end_date ?? 'sekarang'}
                                                {active && (
                                                    <Badge
                                                        variant="secondary"
                                                        className="ml-2 bg-primary/15 text-primary"
                                                    >
                                                        Berlaku
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="flex flex-wrap gap-1">
                                                {emp.changes.map((c) => (
                                                    <Badge
                                                        key={c}
                                                        variant="secondary"
                                                        className="bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400"
                                                    >
                                                        Δ {c}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="mt-2 grid gap-x-6 gap-y-1 text-sm text-muted-foreground sm:grid-cols-2 lg:grid-cols-3">
                                            <span>Jabatan: {emp.position ?? '-'}</span>
                                            <span>Departemen: {emp.department ?? '-'}</span>
                                            <span>Grade: {emp.job_grade ?? '-'}</span>
                                            <span>Atasan: {emp.manager ?? '-'}</span>
                                            <span>Tipe: {emp.employment_type}</span>
                                            <span>Status: {emp.status}</span>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </CardContent>
                </Card>

                <Card className="gap-0 py-0">
                    <CardHeader className="px-5 pt-5">
                        <CardTitle>Riwayat Gaji</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Komponen</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead className="text-right">
                                        Nilai
                                    </TableHead>
                                    <TableHead>Berlaku</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {salary.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Belum ada riwayat gaji.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    salary.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell className="font-medium">
                                                {row.component_name ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {row.type === 'earning'
                                                    ? 'Penghasilan'
                                                    : 'Potongan'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {row.calc_type === 'percentage'
                                                    ? `${row.rate}%`
                                                    : formatRupiah(row.amount)}
                                            </TableCell>
                                            <TableCell>
                                                {row.effective_date ?? '-'}
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
