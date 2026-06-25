import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil, Wallet } from 'lucide-react';
import type { ReactNode } from 'react';
import employees from '@/actions/App/Http/Controllers/EmployeeController';
import salary from '@/actions/App/Http/Controllers/EmployeeSalaryComponentController';
import PageHeader from '@/components/page-header';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatDateID } from '@/lib/format';
import type { EmployeeFull } from '@/types/employee';

type ShowProps = {
    employee: EmployeeFull;
};

const GENDER_LABELS: Record<string, string> = {
    male: 'Laki-laki',
    female: 'Perempuan',
};

function DetailRow({ label, value }: { label: string; value: ReactNode }) {
    const display =
        value === null || value === undefined || value === '' ? '-' : value;

    return (
        <div className="flex flex-col gap-1 border-b border-border/50 pb-3 last:border-0 last:pb-0">
            <span className="text-xs text-muted-foreground">{label}</span>
            <span className="text-sm font-medium text-foreground">
                {display}
            </span>
        </div>
    );
}

export default function EmployeesShow({ employee }: ShowProps) {
    useFlashToast();

    const fullName = `${employee.first_name}${employee.last_name ? ` ${employee.last_name}` : ''}`;
    const employment = employee.current_employment;
    const initials = fullName
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <>
            <Head title={fullName} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader title="Detail Karyawan">
                    <Button asChild variant="outline">
                        <Link href={employees.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    <Button asChild variant="outline">
                        <Link href={salary.index.url(employee.id)}>
                            <Wallet />
                            Komponen Gaji
                        </Link>
                    </Button>
                    <Button asChild variant="success">
                        <Link href={employees.edit.url(employee.id)}>
                            <Pencil />
                            Edit
                        </Link>
                    </Button>
                </PageHeader>

                {/* Profile hero */}
                <Card>
                    <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <span className="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#2F54C9,#6E9BE6)] text-xl font-semibold text-white">
                            {initials}
                        </span>
                        <div className="flex-1">
                            <div className="flex flex-wrap items-center gap-2.5">
                                <h2 className="text-lg font-semibold text-navy">
                                    {fullName}
                                </h2>
                                <StatusBadge status={employee.status} />
                            </div>
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                {employee.employee_no}
                                {employment?.position?.name
                                    ? ` · ${employment.position.name}`
                                    : ''}
                                {employment?.department?.name
                                    ? ` — ${employment.department.name}`
                                    : ''}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-5 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Data Pribadi
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-2">
                            <DetailRow
                                label="Jenis Kelamin"
                                value={
                                    GENDER_LABELS[employee.gender ?? ''] ?? '-'
                                }
                            />
                            <DetailRow
                                label="Tempat Lahir"
                                value={employee.birth_place}
                            />
                            <DetailRow
                                label="Tanggal Lahir"
                                value={formatDateID(employee.birth_date)}
                            />
                            <DetailRow label="Agama" value={employee.religion} />
                            <DetailRow
                                label="Status Pernikahan"
                                value={employee.marital_status}
                            />
                            <DetailRow label="NIK KTP" value={employee.nik_ktp} />
                            <DetailRow label="NPWP" value={employee.npwp} />
                            <DetailRow label="Email" value={employee.email} />
                            <DetailRow label="Telepon" value={employee.phone} />
                            <DetailRow label="Alamat" value={employee.address} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Kepegawaian
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            <DetailRow
                                label="Posisi"
                                value={employment?.position?.name}
                            />
                            <DetailRow
                                label="Departemen"
                                value={employment?.department?.name}
                            />
                            <DetailRow
                                label="Cabang"
                                value={employment?.branch?.name}
                            />
                            <DetailRow
                                label="Job Grade"
                                value={employment?.job_grade?.name}
                            />
                            <DetailRow
                                label="Tanggal Bergabung"
                                value={formatDateID(employee.join_date)}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Rekening Bank
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            {employee.bank_accounts.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada rekening bank.
                                </p>
                            ) : (
                                employee.bank_accounts.map((account) => (
                                    <div
                                        key={account.id}
                                        className="flex items-center justify-between rounded-lg border border-border/60 p-3"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-foreground">
                                                {account.bank_code} ·{' '}
                                                {account.account_no}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {account.account_name}
                                            </p>
                                        </div>
                                        {account.is_primary && (
                                            <span className="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                                                Utama
                                            </span>
                                        )}
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Tanggungan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            {employee.dependents.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada data tanggungan.
                                </p>
                            ) : (
                                employee.dependents.map((dependent) => (
                                    <div
                                        key={dependent.id}
                                        className="flex items-center justify-between rounded-lg border border-border/60 p-3"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-foreground">
                                                {dependent.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {dependent.relationship ?? '-'}
                                            </p>
                                        </div>
                                        <span className="text-xs text-muted-foreground">
                                            {formatDateID(dependent.birth_date)}
                                        </span>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
