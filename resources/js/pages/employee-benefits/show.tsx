import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CircleDollarSign,
    Gift,
    Plus,
    ReceiptText,
    Trash2,
    TrendingDown,
    Wallet,
} from 'lucide-react';
import type { FormEvent } from 'react';
import employeeBenefits from '@/actions/App/Http/Controllers/EmployeeBenefitController';
import ConfirmDialog from '@/components/confirm-dialog';
import { DetailItem } from '@/components/detail/detail-item';
import { InfoHero } from '@/components/detail/info-hero';
import { SectionCard } from '@/components/detail/section-card';
import { StatTile } from '@/components/detail/stat-tile';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import { RupiahInput } from '@/components/ui/rupiah-input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatDateID, formatDateTimeID, formatRupiah } from '@/lib/format';

type Claim = {
    id: number;
    claim_date: string;
    amount: number;
    description: string;
    status: string;
    status_label: string;
    decided_by: string | null;
    decided_at: string | null;
    decision_note: string | null;
};

type EmployeeBenefit = {
    id: number;
    employee_name: string;
    employee_no: string;
    benefit_type_name: string;
    period_year: number;
    quota: number;
    used: number;
    remaining: number;
    notes: string | null;
    claims: Claim[];
};

type ShowProps = {
    employeeBenefit: EmployeeBenefit;
};

const STATUS_STYLES: Record<string, string> = {
    pending:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    approved:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

type ClaimForm = {
    claim_date: string;
    amount: string;
    description: string;
};

const emptyClaim: ClaimForm = {
    claim_date: '',
    amount: '',
    description: '',
};

export default function EmployeeBenefitsShow({ employeeBenefit }: ShowProps) {
    useFlashToast();

    const claimForm = useForm<ClaimForm>(emptyClaim);

    const usedPercent =
        employeeBenefit.quota > 0
            ? Math.min(
                  100,
                  Math.round(
                      (employeeBenefit.used / employeeBenefit.quota) * 100,
                  ),
              )
            : 0;
    const initials = employeeBenefit.employee_name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    function handleDelete() {
        router.delete(employeeBenefits.destroy.url(employeeBenefit.id));
    }

    function submitClaim(event: FormEvent) {
        event.preventDefault();

        claimForm.transform((data) => ({
            ...data,
            amount: data.amount ? Number(data.amount) : null,
        }));

        claimForm.post(employeeBenefits.storeClaim.url(employeeBenefit.id), {
            preserveScroll: true,
            onSuccess: () => claimForm.reset(),
        });
    }

    function deleteClaim(claimId: number) {
        router.delete(employeeBenefits.destroyClaim.url(claimId), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title={`Benefit — ${employeeBenefit.employee_name}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader title="Detail Benefit Karyawan">
                    <Button asChild variant="secondary">
                        <Link href={employeeBenefits.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    <ConfirmDialog
                        title="Hapus Benefit Karyawan"
                        description={`Yakin ingin menghapus benefit "${employeeBenefit.benefit_type_name}" untuk ${employeeBenefit.employee_name}?`}
                        confirmLabel="Hapus"
                        onConfirm={handleDelete}
                        trigger={
                            <Button variant="destructive">
                                <Trash2 />
                                Hapus
                            </Button>
                        }
                    />
                </PageHeader>

                <InfoHero
                    initials={initials}
                    title={employeeBenefit.employee_name}
                    subtitle={`${employeeBenefit.employee_no} · ${employeeBenefit.benefit_type_name}`}
                    badges={
                        <Badge
                            variant="secondary"
                            className="bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400"
                        >
                            Tahun {employeeBenefit.period_year}
                        </Badge>
                    }
                    aside={
                        <>
                            <p className="text-xs text-muted-foreground">
                                Sisa Plafon
                            </p>
                            <p
                                className={`text-2xl font-semibold ${
                                    employeeBenefit.remaining > 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'
                                }`}
                            >
                                {formatRupiah(employeeBenefit.remaining)}
                            </p>
                        </>
                    }
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <StatTile
                        label="Plafon"
                        value={formatRupiah(employeeBenefit.quota)}
                        icon={Wallet}
                        accent="blue"
                    />
                    <StatTile
                        label="Terpakai"
                        value={formatRupiah(employeeBenefit.used)}
                        icon={TrendingDown}
                        accent="amber"
                        sub={`${usedPercent}% dari plafon`}
                    />
                    <StatTile
                        label="Sisa"
                        value={formatRupiah(employeeBenefit.remaining)}
                        icon={CircleDollarSign}
                        accent={employeeBenefit.remaining > 0 ? 'green' : 'red'}
                    />
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <SectionCard
                        title="Ringkasan Plafon"
                        icon={Gift}
                        contentClassName="grid gap-3"
                    >
                        <DetailItem
                            label="Karyawan"
                            value={
                                <span>
                                    {employeeBenefit.employee_name}
                                    <span className="ml-1 text-xs text-muted-foreground">
                                        ({employeeBenefit.employee_no})
                                    </span>
                                </span>
                            }
                        />
                        <DetailItem
                            label="Jenis Benefit"
                            value={employeeBenefit.benefit_type_name}
                        />
                        <DetailItem
                            label="Tahun"
                            value={employeeBenefit.period_year}
                        />
                        <DetailItem
                            label="Plafon"
                            value={formatRupiah(employeeBenefit.quota)}
                        />
                        <DetailItem
                            label="Terpakai"
                            value={formatRupiah(employeeBenefit.used)}
                        />
                        <DetailItem
                            label="Sisa"
                            value={
                                <span
                                    className={
                                        employeeBenefit.remaining > 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-red-600 dark:text-red-400'
                                    }
                                >
                                    {formatRupiah(employeeBenefit.remaining)}
                                </span>
                            }
                        />
                        <DetailItem
                            label="Catatan"
                            value={employeeBenefit.notes}
                        />

                        <div className="flex flex-col gap-1.5 pt-1">
                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                <span>Penggunaan Plafon</span>
                                <span className="font-medium tabular-nums">
                                    {usedPercent}%
                                </span>
                            </div>
                            <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    className={
                                        usedPercent >= 100
                                            ? 'h-full rounded-full bg-red-500'
                                            : usedPercent >= 80
                                              ? 'h-full rounded-full bg-amber-500'
                                              : 'h-full rounded-full bg-emerald-500'
                                    }
                                    style={{ width: `${usedPercent}%` }}
                                />
                            </div>
                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                <span>
                                    {formatRupiah(employeeBenefit.used)}
                                </span>
                                <span>
                                    {formatRupiah(employeeBenefit.quota)}
                                </span>
                            </div>
                        </div>
                    </SectionCard>

                    <SectionCard
                        title="Klaim Benefit"
                        icon={ReceiptText}
                        description="Klaim baru diajukan untuk persetujuan melalui Inbox Approval."
                        actions={
                            <span className="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                {employeeBenefit.claims.length}
                            </span>
                        }
                        contentClassName="flex flex-col gap-4"
                    >
                        <div className="flex flex-col gap-2">
                            {employeeBenefit.claims.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada klaim benefit.
                                </p>
                            ) : (
                                employeeBenefit.claims.map((claim) => (
                                    <div
                                        key={claim.id}
                                        className="flex flex-col gap-2 rounded-md border border-border/50 px-3 py-3"
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="flex flex-col gap-1">
                                                <span className="text-sm font-medium text-foreground">
                                                    {claim.description}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {formatDateID(
                                                        claim.claim_date,
                                                    )}{' '}
                                                    ·{' '}
                                                    {formatRupiah(claim.amount)}
                                                </span>
                                            </div>
                                            <Badge
                                                variant="secondary"
                                                className={
                                                    STATUS_STYLES[claim.status]
                                                }
                                            >
                                                {claim.status_label}
                                            </Badge>
                                        </div>

                                        {claim.decided_by ||
                                        claim.decided_at ? (
                                            <span className="text-xs text-muted-foreground">
                                                Diputuskan oleh{' '}
                                                {claim.decided_by ?? '-'}
                                                {claim.decided_at
                                                    ? ` · ${formatDateTimeID(claim.decided_at)}`
                                                    : ''}
                                            </span>
                                        ) : null}
                                        {claim.decision_note ? (
                                            <span className="text-xs text-muted-foreground">
                                                Alasan: {claim.decision_note}
                                            </span>
                                        ) : null}

                                        <div className="flex flex-wrap items-center gap-2">
                                            <ConfirmDialog
                                                title="Hapus Klaim"
                                                description={`Yakin ingin menghapus klaim "${claim.description}"?`}
                                                confirmLabel="Hapus"
                                                onConfirm={() =>
                                                    deleteClaim(claim.id)
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
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>

                        <form
                            onSubmit={submitClaim}
                            className="flex flex-col gap-3 border-t border-border/50 pt-4"
                        >
                            <p className="text-sm font-semibold text-navy">
                                Tambah Klaim
                            </p>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="claim_date">
                                        Tanggal Klaim <RequiredMark />
                                    </Label>
                                    <DatePicker
                                        id="claim_date"
                                        value={claimForm.data.claim_date}
                                        onChange={(value) =>
                                            claimForm.setData(
                                                'claim_date',
                                                value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={claimForm.errors.claim_date}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="amount">
                                        Jumlah <RequiredMark />
                                    </Label>
                                    <RupiahInput
                                        id="amount"
                                        value={claimForm.data.amount}
                                        onChange={(value) =>
                                            claimForm.setData('amount', value)
                                        }
                                        placeholder="500.000"
                                    />
                                    <InputError
                                        message={claimForm.errors.amount}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Deskripsi <RequiredMark />
                                </Label>
                                <textarea
                                    id="description"
                                    value={claimForm.data.description}
                                    onChange={(e) =>
                                        claimForm.setData(
                                            'description',
                                            e.target.value,
                                        )
                                    }
                                    rows={2}
                                    placeholder="Deskripsi klaim benefit"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                />
                                <InputError
                                    message={claimForm.errors.description}
                                />
                            </div>
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={claimForm.processing}
                                >
                                    <Plus />
                                    Tambah Klaim
                                </Button>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            </div>
        </>
    );
}
