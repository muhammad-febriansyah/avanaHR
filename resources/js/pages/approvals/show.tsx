import { Head, useForm } from '@inertiajs/react';
import { Check, RotateCcw, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import approvals from '@/actions/App/Http/Controllers/ApprovalController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    approved:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    revision: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    skipped: 'bg-muted text-muted-foreground',
};

const APPROVER_TYPE_LABELS: Record<string, string> = {
    manager: 'Atasan Langsung',
    department_head: 'Kepala Departemen',
    role: 'Peran',
    user: 'Pengguna',
};

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

    return (
        <>
            <Head title={`Approval #${request.id}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={request.title}
                    description={`${request.flow_name ?? 'Tanpa alur'} • Pemohon: ${request.requester ?? '—'}`}
                >
                    <Badge
                        variant="secondary"
                        className={STATUS_STYLES[request.status]}
                    >
                        {request.status}
                    </Badge>
                </PageHeader>

                <div className="grid gap-5 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Langkah Persetujuan</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {request.steps.map((step) => (
                                <div
                                    key={step.order}
                                    className={`flex items-start justify-between rounded-lg border p-3 ${
                                        step.is_current
                                            ? 'border-primary bg-primary/5'
                                            : ''
                                    }`}
                                >
                                    <div>
                                        <div className="font-medium">
                                            Langkah {step.order} —{' '}
                                            {APPROVER_TYPE_LABELS[
                                                step.approver_type
                                            ] ?? step.approver_type}
                                            {step.approver_ref
                                                ? ` (${step.approver_ref})`
                                                : ''}
                                        </div>
                                        {step.reason ? (
                                            <div className="mt-1 text-sm text-muted-foreground">
                                                {step.reason}
                                            </div>
                                        ) : null}
                                        {step.acted_at ? (
                                            <div className="mt-1 text-xs text-muted-foreground">
                                                {step.acted_at}
                                            </div>
                                        ) : null}
                                    </div>
                                    <Badge
                                        variant="secondary"
                                        className={STATUS_STYLES[step.status]}
                                    >
                                        {step.status}
                                    </Badge>
                                </div>
                            ))}

                            {request.history.length > 0 ? (
                                <div className="pt-2">
                                    <div className="mb-2 text-sm font-medium">
                                        Riwayat
                                    </div>
                                    <ul className="space-y-1 text-sm text-muted-foreground">
                                        {request.history.map((entry, idx) => (
                                            <li key={idx}>
                                                {entry.at} — {entry.actor}:{' '}
                                                {entry.action}
                                                {entry.reason
                                                    ? ` (${entry.reason})`
                                                    : ''}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ) : null}
                        </CardContent>
                    </Card>

                    {canAct ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>Keputusan</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={onSubmit}
                                    className="space-y-3"
                                >
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
                                            className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
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
                                            variant="outline"
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
                            </CardContent>
                        </Card>
                    ) : null}
                </div>
            </div>
        </>
    );
}
