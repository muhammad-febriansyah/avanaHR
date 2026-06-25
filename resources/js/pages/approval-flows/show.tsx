import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2, Users } from 'lucide-react';
import type { FormEvent } from 'react';
import approvalFlows from '@/actions/App/Http/Controllers/ApprovalFlowController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Option = { value: string; label: string };

type Step = {
    id: number;
    order: number;
    approver_type: string;
    approver_ref: string | null;
    mode: string;
    min_approvals: number;
    sla_hours: number | null;
    allow_delegate: boolean;
};

type Flow = {
    id: number;
    name: string;
    transaction_type: string;
    is_active: boolean;
    steps: Step[];
};

type ShowProps = {
    flow: Flow;
    transactionTypes: Option[];
    approverTypes: Option[];
};

function label(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

type StepForm = {
    approver_type: string;
    approver_ref: string;
    mode: string;
    min_approvals: string;
    sla_hours: string;
    allow_delegate: boolean;
};

export default function ApprovalFlowShow({
    flow,
    transactionTypes,
    approverTypes,
}: ShowProps) {
    useFlashToast();

    const form = useForm<StepForm>({
        approver_type: 'manager',
        approver_ref: '',
        mode: 'all',
        min_approvals: '1',
        sla_hours: '24',
        allow_delegate: false,
    });

    function addStep(event: FormEvent) {
        event.preventDefault();
        form.post(approvalFlows.storeStep.url(flow.id), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function removeStep(id: number) {
        router.delete(approvalFlows.destroyStep.url(id), { preserveScroll: true });
    }

    function toggleActive() {
        router.patch(approvalFlows.update.url(flow.id), {}, { preserveScroll: true });
    }

    return (
        <>
            <Head title={flow.name} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={flow.name}
                    description={`Transaksi: ${label(transactionTypes, flow.transaction_type)}`}
                >
                    <Button variant="outline" onClick={toggleActive}>
                        {flow.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                    </Button>
                    <Button asChild variant="outline">
                        <a href={approvalFlows.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </a>
                    </Button>
                </PageHeader>

                <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                Langkah Persetujuan
                                <Badge
                                    variant="secondary"
                                    className={
                                        flow.is_active
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                            : 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300'
                                    }
                                >
                                    {flow.is_active ? 'Aktif' : 'Nonaktif'}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {flow.steps.length === 0 ? (
                                <div className="flex flex-col items-center justify-center gap-3 py-10 text-center">
                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                        <Users className="size-6" />
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Belum ada langkah. Tambahkan di samping.
                                    </p>
                                </div>
                            ) : (
                                <ol className="flex flex-col gap-3">
                                    {flow.steps.map((step) => (
                                        <li
                                            key={step.id}
                                            className="flex items-center gap-3 rounded-lg border p-3"
                                        >
                                            <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                                {step.order}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <div className="font-medium">
                                                    {label(approverTypes, step.approver_type)}
                                                    {step.approver_ref
                                                        ? ` · ${step.approver_ref}`
                                                        : ''}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {step.mode === 'all'
                                                        ? 'Semua penyetuju'
                                                        : 'Salah satu'}{' '}
                                                    · min {step.min_approvals}
                                                    {step.sla_hours
                                                        ? ` · SLA ${step.sla_hours} jam`
                                                        : ''}
                                                    {step.allow_delegate
                                                        ? ' · boleh delegasi'
                                                        : ''}
                                                </div>
                                            </div>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() => removeStep(step.id)}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </li>
                                    ))}
                                </ol>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="h-fit">
                        <CardHeader>
                            <CardTitle className="text-base">Tambah Langkah</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={addStep} className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="st-type">Penyetuju</Label>
                                    <Select
                                        value={form.data.approver_type}
                                        onValueChange={(value) =>
                                            form.setData('approver_type', value)
                                        }
                                    >
                                        <SelectTrigger id="st-type" className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {approverTypes.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="st-ref">
                                        Referensi (role / nama, opsional)
                                    </Label>
                                    <Input
                                        id="st-ref"
                                        value={form.data.approver_ref}
                                        onChange={(e) =>
                                            form.setData('approver_ref', e.target.value)
                                        }
                                        placeholder="Mis. finance"
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="st-mode">Mode</Label>
                                        <Select
                                            value={form.data.mode}
                                            onValueChange={(value) =>
                                                form.setData('mode', value)
                                            }
                                        >
                                            <SelectTrigger id="st-mode" className="w-full">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    Semua
                                                </SelectItem>
                                                <SelectItem value="any">
                                                    Salah satu
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="st-min">Min. Setuju</Label>
                                        <Input
                                            id="st-min"
                                            type="number"
                                            min="1"
                                            max="10"
                                            value={form.data.min_approvals}
                                            onChange={(e) =>
                                                form.setData(
                                                    'min_approvals',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="st-sla">SLA (jam, opsional)</Label>
                                    <Input
                                        id="st-sla"
                                        type="number"
                                        min="1"
                                        max="720"
                                        value={form.data.sla_hours}
                                        onChange={(e) =>
                                            form.setData('sla_hours', e.target.value)
                                        }
                                    />
                                </div>

                                <label className="flex items-center gap-2 text-sm">
                                    <Checkbox
                                        checked={form.data.allow_delegate}
                                        onCheckedChange={(checked) =>
                                            form.setData('allow_delegate', checked === true)
                                        }
                                    />
                                    Izinkan delegasi
                                </label>

                                {form.errors.min_approvals && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.min_approvals}
                                    </p>
                                )}

                                <Button type="submit" disabled={form.processing}>
                                    <Plus />
                                    Tambah Langkah
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
