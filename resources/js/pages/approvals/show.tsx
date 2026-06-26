import { Head, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    GitBranch,
    History as HistoryIcon,
    RotateCcw,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import approvals from '@/actions/App/Http/Controllers/ApprovalController';
import { InfoHero } from '@/components/detail/info-hero';
import { SectionCard } from '@/components/detail/section-card';
import { Timeline } from '@/components/detail/timeline';
import type { TimelineAccent } from '@/components/detail/timeline';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Step = {
    order: number;
    approver_type: string;
    approver_ref: string | null;
    mode: string;
    status: string;
    reason: string | null;
    acted_at: string | null;
    is_current: boolean;
};

type HistoryEntry = {
    actor: string | null;
    action: string;
    reason: string | null;
    at: string | null;
};

type ApprovalRequest = {
    id: number;
    type: string;
    title: string;
    requester: string | null;
    status: string;
    flow_name: string | null;
    submitted_at: string | null;
    steps: Step[];
    history: HistoryEntry[];
};

type ShowProps = {
    request: ApprovalRequest;
    canAct: boolean;
};

const STATUS_STYLES: Record<string, string> = {
    pending:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    approved:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    revision: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    skipped: 'bg-muted text-muted-foreground',
};

const STATUS_LABELS: Record<string, string> = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    revision: 'Revisi',
    skipped: 'Dilewati',
};

const STATUS_ACCENT: Record<string, TimelineAccent> = {
    pending: 'amber',
    approved: 'green',
    approve: 'green',
    rejected: 'red',
    reject: 'red',
    revision: 'blue',
    revise: 'blue',
    submit: 'slate',
    skipped: 'slate',
};

const APPROVER_TYPE_LABELS: Record<string, string> = {
    manager: 'Atasan Langsung',
    department_head: 'Kepala Departemen',
    role: 'Peran',
    user: 'Pengguna',
};

function statusLabel(status: string): string {
    return STATUS_LABELS[status] ?? status;
}

export default function ApprovalShow({ request, canAct }: ShowProps) {
    useFlashToast();

    const [reason, setReason] = useState('');
    const form = useForm({ action: 'approve', reason: '' });

    function act(action: 'approve' | 'reject' | 'revise') {
        form.transform(() => ({ action, reason }));
        form.post(approvals.act.url(request.id), { preserveScroll: true });
    }

    function onSubmit(event: FormEvent) {
        event.preventDefault();
    }

    const approvedSteps = request.steps.filter(
        (step) => step.status === 'approved',
    ).length;

    return (
        <>
            <Head title={`Approval #${request.id}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader title="Detail Persetujuan">
                    <Button asChild variant="secondary">
                        <a href={approvals.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </a>
                    </Button>
                </PageHeader>

                <InfoHero
                    icon={GitBranch}
                    title={request.title}
                    subtitle={`${request.flow_name ?? 'Tanpa alur'} · Pemohon: ${request.requester ?? '—'}${request.submitted_at ? ` · ${request.submitted_at}` : ''}`}
                    badges={
                        <Badge
                            variant="secondary"
                            className={STATUS_STYLES[request.status]}
                        >
                            {statusLabel(request.status)}
                        </Badge>
                    }
                    aside={
                        <>
                            <p className="text-xs text-muted-foreground">
                                Progres
                            </p>
                            <p className="text-lg font-semibold text-navy">
                                {approvedSteps}/{request.steps.length} langkah
                            </p>
                        </>
                    }
                />

                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="flex flex-col gap-5 lg:col-span-2">
                        <SectionCard
                            title="Langkah Persetujuan"
                            icon={GitBranch}
                            contentClassName="flex flex-col gap-3"
                        >
                            {request.steps.map((step) => (
                                <div
                                    key={step.order}
                                    className={`flex items-start gap-3 rounded-lg border p-3 ${
                                        step.is_current
                                            ? 'border-primary bg-primary/5'
                                            : 'border-border/60'
                                    }`}
                                >
                                    <span
                                        className={`flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold ${
                                            step.status === 'approved'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                : step.status === 'rejected'
                                                  ? 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400'
                                                  : step.is_current
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground'
                                        }`}
                                    >
                                        {step.status === 'approved' ? (
                                            <Check className="size-4" />
                                        ) : (
                                            step.order
                                        )}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-medium text-foreground">
                                                {APPROVER_TYPE_LABELS[
                                                    step.approver_type
                                                ] ?? step.approver_type}
                                                {step.approver_ref
                                                    ? ` (${step.approver_ref})`
                                                    : ''}
                                            </span>
                                            {step.is_current ? (
                                                <Badge
                                                    variant="secondary"
                                                    className="bg-primary/10 text-primary"
                                                >
                                                    Saat ini
                                                </Badge>
                                            ) : null}
                                        </div>
                                        {step.reason ? (
                                            <div className="mt-1 text-sm text-muted-foreground">
                                                {step.reason}
                                            </div>
                                        ) : null}
                                        {step.acted_at ? (
                                            <div className="mt-1 text-xs text-muted-foreground tabular-nums">
                                                {step.acted_at}
                                            </div>
                                        ) : null}
                                    </div>
                                    <Badge
                                        variant="secondary"
                                        className={STATUS_STYLES[step.status]}
                                    >
                                        {statusLabel(step.status)}
                                    </Badge>
                                </div>
                            ))}
                        </SectionCard>

                        <SectionCard title="Riwayat" icon={HistoryIcon}>
                            <Timeline
                                emptyText="Belum ada aktivitas."
                                items={request.history.map((entry) => ({
                                    title: (
                                        <>
                                            {entry.actor ?? 'Sistem'} —{' '}
                                            {entry.action}
                                        </>
                                    ),
                                    description: entry.reason ?? undefined,
                                    meta: entry.at ?? undefined,
                                    accent:
                                        STATUS_ACCENT[entry.action] ?? 'slate',
                                }))}
                            />
                        </SectionCard>
                    </div>

                    {canAct ? (
                        <SectionCard
                            title="Keputusan"
                            icon={Check}
                            className="h-fit"
                        >
                            <form onSubmit={onSubmit} className="space-y-3">
                                <div className="space-y-1">
                                    <Label htmlFor="reason">
                                        Catatan / Alasan
                                    </Label>
                                    <textarea
                                        id="reason"
                                        value={reason}
                                        onChange={(e) =>
                                            setReason(e.target.value)
                                        }
                                        rows={3}
                                        placeholder="Wajib untuk Tolak / Revisi"
                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    {form.errors.reason ? (
                                        <p className="text-sm text-red-600">
                                            {form.errors.reason}
                                        </p>
                                    ) : null}
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Button
                                        type="button"
                                        disabled={form.processing}
                                        onClick={() => act('approve')}
                                    >
                                        <Check />
                                        Setujui
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        disabled={form.processing}
                                        onClick={() => act('revise')}
                                    >
                                        <RotateCcw />
                                        Minta Revisi
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        disabled={form.processing}
                                        onClick={() => act('reject')}
                                    >
                                        <X />
                                        Tolak
                                    </Button>
                                </div>
                            </form>
                        </SectionCard>
                    ) : null}
                </div>
            </div>
        </>
    );
}
