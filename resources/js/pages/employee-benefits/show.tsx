import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import employeeBenefits from '@/actions/App/Http/Controllers/EmployeeBenefitController';
import ConfirmDialog from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    can_decide: boolean;
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
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
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

    const [rejectClaimId, setRejectClaimId] = useState<number | null>(null);

    const rejectForm = useForm<{ status: string; decision_note: string }>({
        status: 'rejected',
        decision_note: '',
    });

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

    function approveClaim(claimId: number) {
        router.patch(
            employeeBenefits.decideClaim.url(claimId),
            { status: 'approved' },
            { preserveScroll: true },
        );
    }

    function openReject(claimId: number) {
        rejectForm.reset();
        rejectForm.clearErrors();
        setRejectClaimId(claimId);
    }

    function submitReject(event: FormEvent) {
        event.preventDefault();

        if (rejectClaimId === null) {
            return;
        }

        rejectForm.patch(employeeBenefits.decideClaim.url(rejectClaimId), {
            preserveScroll: true,
            onSuccess: () => {
                rejectForm.reset();
                setRejectClaimId(null);
            },
        });
    }

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
                <PageHeader title={`Benefit — ${employeeBenefit.employee_name}`}>
                    <Button asChild variant="outline">
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

                <div className="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Ringkasan Plafon
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            <DetailRow
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
                            <DetailRow
                                label="Jenis Benefit"
                                value={employeeBenefit.benefit_type_name}
                            />
                            <DetailRow
                                label="Tahun"
                                value={employeeBenefit.period_year}
                            />
                            <DetailRow
                                label="Plafon"
                                value={formatRupiah(employeeBenefit.quota)}
                            />
                            <DetailRow
                                label="Terpakai"
                                value={formatRupiah(employeeBenefit.used)}
                            />
                            <DetailRow
                                label="Sisa"
                                value={
                                    <span
                                        className={
                                            employeeBenefit.remaining > 0
                                                ? 'text-emerald-600 dark:text-emerald-400'
                                                : 'text-red-600 dark:text-red-400'
                                        }
                                    >
                                        {formatRupiah(
                                            employeeBenefit.remaining,
                                        )}
                                    </span>
                                }
                            />
                            <DetailRow
                                label="Catatan"
                                value={employeeBenefit.notes ?? '-'}
                            />

                            <div className="flex flex-col gap-1.5 pt-1">
                                <div className="flex items-center justify-between text-xs text-muted-foreground">
                                    <span>Penggunaan</span>
                                    <span>{usedPercent}%</span>
                                </div>
                                <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        className={
                                            usedPercent >= 100
                                                ? 'h-full rounded-full bg-red-500'
                                                : 'h-full rounded-full bg-emerald-500'
                                        }
                                        style={{ width: `${usedPercent}%` }}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Klaim Benefit
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
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
                                                        {formatRupiah(
                                                            claim.amount,
                                                        )}
                                                    </span>
                                                </div>
                                                <Badge
                                                    variant="secondary"
                                                    className={
                                                        STATUS_STYLES[
                                                            claim.status
                                                        ]
                                                    }
                                                >
                                                    {claim.status_label}
                                                </Badge>
                                            </div>

                                            {(claim.decided_by ||
                                                claim.decided_at) && (
                                                <span className="text-xs text-muted-foreground">
                                                    Diputuskan oleh{' '}
                                                    {claim.decided_by ?? '-'}
                                                    {claim.decided_at
                                                        ? ` · ${formatDateTimeID(claim.decided_at)}`
                                                        : ''}
                                                </span>
                                            )}
                                            {claim.decision_note && (
                                                <span className="text-xs text-muted-foreground">
                                                    Alasan: {claim.decision_note}
                                                </span>
                                            )}

                                            <div className="flex flex-wrap items-center gap-2">
                                                {claim.can_decide && (
                                                    <>
                                                        <Button
                                                            size="sm"
                                                            variant="success"
                                                            onClick={() =>
                                                                approveClaim(
                                                                    claim.id,
                                                                )
                                                            }
                                                        >
                                                            <CheckCircle />
                                                            Setujui
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            className="text-red-700 hover:text-red-700 dark:text-red-400"
                                                            onClick={() =>
                                                                openReject(
                                                                    claim.id,
                                                                )
                                                            }
                                                        >
                                                            <X />
                                                            Tolak
                                                        </Button>
                                                    </>
                                                )}
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
                                                            variant="outline"
                                                            className="text-red-700 hover:text-red-700 dark:text-red-400"
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
                                        <Input
                                            id="claim_date"
                                            type="date"
                                            value={claimForm.data.claim_date}
                                            onChange={(e) =>
                                                claimForm.setData(
                                                    'claim_date',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Pilih tanggal"
                                        />
                                        <InputError
                                            message={
                                                claimForm.errors.claim_date
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="amount">
                                            Jumlah (Rp) <RequiredMark />
                                        </Label>
                                        <Input
                                            id="amount"
                                            type="number"
                                            min={0}
                                            value={claimForm.data.amount}
                                            onChange={(e) =>
                                                claimForm.setData(
                                                    'amount',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Mis. 500000"
                                        />
                                        {claimForm.data.amount &&
                                            Number(claimForm.data.amount) >
                                                0 && (
                                                <p className="text-xs text-muted-foreground">
                                                    {formatRupiah(
                                                        Number(
                                                            claimForm.data
                                                                .amount,
                                                        ),
                                                    )}
                                                </p>
                                            )}
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
                                        className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
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
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Dialog
                open={rejectClaimId !== null}
                onOpenChange={(value) => {
                    if (!value) {
                        setRejectClaimId(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tolak Klaim Benefit</DialogTitle>
                    </DialogHeader>

                    <form
                        id="reject-claim-form"
                        onSubmit={submitReject}
                        className="grid gap-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="decision_note">
                                Alasan Penolakan
                            </Label>
                            <textarea
                                id="decision_note"
                                value={rejectForm.data.decision_note}
                                onChange={(e) =>
                                    rejectForm.setData(
                                        'decision_note',
                                        e.target.value,
                                    )
                                }
                                rows={3}
                                placeholder="Alasan penolakan (opsional)"
                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError
                                message={rejectForm.errors.decision_note}
                            />
                        </div>
                    </form>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setRejectClaimId(null)}
                        >
                            <X />
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="reject-claim-form"
                            variant="destructive"
                            disabled={rejectForm.processing}
                        >
                            <X />
                            Tolak
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
