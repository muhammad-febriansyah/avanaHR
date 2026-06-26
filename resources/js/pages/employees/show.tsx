import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Banknote,
    Briefcase,
    Building2,
    CakeSlice,
    CalendarDays,
    CreditCard,
    History,
    IdCard,
    Mail,
    MapPin,
    Pencil,
    Phone,
    User,
    Users,
    Wallet,
} from 'lucide-react';
import employees from '@/actions/App/Http/Controllers/EmployeeController';
import employeeHistory from '@/actions/App/Http/Controllers/EmployeeHistoryController';
import salary from '@/actions/App/Http/Controllers/EmployeeSalaryComponentController';
import taxBpjs from '@/actions/App/Http/Controllers/EmployeeTaxBpjsController';
import { DetailItem } from '@/components/detail/detail-item';
import { InfoHero } from '@/components/detail/info-hero';
import { SectionCard } from '@/components/detail/section-card';
import { StatTile } from '@/components/detail/stat-tile';
import PageHeader from '@/components/page-header';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
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

/** Human "X tahun Y bulan" tenure from a join date. */
function tenure(joinDate?: string | null): string {
    if (!joinDate) {
        return '—';
    }

    const start = new Date(joinDate);

    if (Number.isNaN(start.getTime())) {
        return '—';
    }

    const now = new Date();
    let months =
        (now.getFullYear() - start.getFullYear()) * 12 +
        (now.getMonth() - start.getMonth());

    if (now.getDate() < start.getDate()) {
        months -= 1;
    }

    if (months < 0) {
        return '—';
    }

    const years = Math.floor(months / 12);
    const restMonths = months % 12;
    const parts: string[] = [];

    if (years > 0) {
        parts.push(`${years} thn`);
    }

    parts.push(`${restMonths} bln`);

    return parts.join(' ');
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
                    <Button asChild variant="secondary">
                        <Link href={employees.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    <Button asChild variant="secondary">
                        <Link href={salary.index.url(employee.id)}>
                            <Wallet />
                            Komponen Gaji
                        </Link>
                    </Button>
                    <Button asChild variant="secondary">
                        <Link href={taxBpjs.index.url(employee.id)}>
                            <Wallet />
                            Pajak & BPJS
                        </Link>
                    </Button>
                    <Button asChild variant="secondary">
                        <Link href={employeeHistory.index.url(employee.id)}>
                            <History />
                            Riwayat
                        </Link>
                    </Button>
                    <Button asChild variant="success">
                        <Link href={employees.edit.url(employee.id)}>
                            <Pencil />
                            Edit
                        </Link>
                    </Button>
                </PageHeader>

                <InfoHero
                    initials={initials}
                    title={fullName}
                    badges={<StatusBadge status={employee.status} />}
                    subtitle={
                        <>
                            {employee.employee_no}
                            {employment?.position?.name
                                ? ` · ${employment.position.name}`
                                : ''}
                            {employment?.department?.name
                                ? ` — ${employment.department.name}`
                                : ''}
                        </>
                    }
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatTile
                        label="Posisi"
                        value={employment?.position?.name ?? '—'}
                        icon={Briefcase}
                        accent="blue"
                        sub={employment?.job_grade?.name ?? undefined}
                    />
                    <StatTile
                        label="Departemen"
                        value={employment?.department?.name ?? '—'}
                        icon={Building2}
                        accent="violet"
                        sub={employment?.branch?.name ?? undefined}
                    />
                    <StatTile
                        label="Bergabung"
                        value={formatDateID(employee.join_date)}
                        icon={CalendarDays}
                        accent="green"
                    />
                    <StatTile
                        label="Masa Kerja"
                        value={tenure(employee.join_date)}
                        icon={History}
                        accent="amber"
                    />
                </div>

                <div className="grid gap-5 lg:grid-cols-3">
                    <SectionCard
                        title="Data Pribadi"
                        icon={User}
                        className="lg:col-span-2"
                        contentClassName="grid gap-3 sm:grid-cols-2"
                    >
                        <DetailItem
                            label="Jenis Kelamin"
                            value={GENDER_LABELS[employee.gender ?? ''] ?? null}
                            icon={User}
                        />
                        <DetailItem
                            label="Tempat Lahir"
                            value={employee.birth_place}
                            icon={MapPin}
                        />
                        <DetailItem
                            label="Tanggal Lahir"
                            value={formatDateID(employee.birth_date)}
                            icon={CakeSlice}
                        />
                        <DetailItem label="Agama" value={employee.religion} />
                        <DetailItem
                            label="Status Pernikahan"
                            value={employee.marital_status}
                        />
                        <DetailItem
                            label="NIK KTP"
                            value={employee.nik_ktp}
                            icon={IdCard}
                        />
                        <DetailItem
                            label="NPWP"
                            value={employee.npwp}
                            icon={IdCard}
                        />
                        <DetailItem
                            label="Email"
                            value={employee.email}
                            icon={Mail}
                        />
                        <DetailItem
                            label="Telepon"
                            value={employee.phone}
                            icon={Phone}
                        />
                        <DetailItem
                            label="Alamat"
                            value={employee.address}
                            icon={MapPin}
                            className="sm:col-span-2"
                        />
                    </SectionCard>

                    <SectionCard
                        title="Kepegawaian"
                        icon={Briefcase}
                        contentClassName="grid gap-3"
                    >
                        <DetailItem
                            label="Posisi"
                            value={employment?.position?.name}
                            icon={Briefcase}
                        />
                        <DetailItem
                            label="Departemen"
                            value={employment?.department?.name}
                            icon={Building2}
                        />
                        <DetailItem
                            label="Cabang"
                            value={employment?.branch?.name}
                            icon={Building2}
                        />
                        <DetailItem
                            label="Job Grade"
                            value={employment?.job_grade?.name}
                        />
                        <DetailItem
                            label="Tanggal Bergabung"
                            value={formatDateID(employee.join_date)}
                            icon={CalendarDays}
                        />
                    </SectionCard>

                    <SectionCard
                        title="Rekening Bank"
                        icon={CreditCard}
                        actions={
                            <span className="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                {employee.bank_accounts.length}
                            </span>
                        }
                        contentClassName="flex flex-col gap-3"
                    >
                        {employee.bank_accounts.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada rekening bank.
                            </p>
                        ) : (
                            employee.bank_accounts.map((account) => (
                                <div
                                    key={account.id}
                                    className="flex items-center gap-3 rounded-lg border border-border/60 p-3"
                                >
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                        <Banknote className="size-4" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-foreground">
                                            {account.bank_code} ·{' '}
                                            {account.account_no}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {account.account_name}
                                        </p>
                                    </div>
                                    {account.is_primary ? (
                                        <span className="shrink-0 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                                            Utama
                                        </span>
                                    ) : null}
                                </div>
                            ))
                        )}
                    </SectionCard>

                    <SectionCard
                        title="Tanggungan"
                        icon={Users}
                        className="lg:col-span-2"
                        actions={
                            <span className="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                {employee.dependents.length}
                            </span>
                        }
                        contentClassName="grid gap-3 sm:grid-cols-2"
                    >
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
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-foreground">
                                            {dependent.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {dependent.relationship ?? '-'}
                                        </p>
                                    </div>
                                    <span className="shrink-0 text-xs text-muted-foreground tabular-nums">
                                        {formatDateID(dependent.birth_date)}
                                    </span>
                                </div>
                            ))
                        )}
                    </SectionCard>
                </div>
            </div>
        </>
    );
}
