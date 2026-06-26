import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CalendarClock,
    CalendarX,
    CheckCircle,
    MinusCircle,
    Repeat,
    ShieldCheck,
    Trash2,
} from 'lucide-react';
import movements from '@/actions/App/Http/Controllers/EmployeeMovementController';
import ConfirmDialog from '@/components/confirm-dialog';
import { DetailItem } from '@/components/detail/detail-item';
import { InfoHero } from '@/components/detail/info-hero';
import { SectionCard } from '@/components/detail/section-card';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatDateID } from '@/lib/format';

type Snap = {
    position: string | null;
    department: string | null;
    job_grade: string | null;
    manager: string | null;
    employment_type: string | null;
    status: string | null;
};

type ClearanceItem = {
    id: number;
    category: string;
    label: string;
    status: string;
    status_label: string;
    note: string | null;
    completed_by: string | null;
    completed_at: string | null;
};

type Clearance = {
    required: boolean;
    pending_count: number;
    items: ClearanceItem[];
};

type Movement = {
    id: number;
    employee_name: string;
    employee_no: string;
    type: string;
    type_label: string;
    effective_date: string;
    status: string;
    status_label: string;
    reason: string | null;
    requires_clearance: boolean;
    applied_at: string | null;
    applied_by: string | null;
    can_apply: boolean;
    can_schedule: boolean;
    can_unschedule: boolean;
    before: Snap | null;
    after: Snap | null;
    clearance: Clearance | null;
};

type ShowProps = {
    movement: Movement;
};

const TYPE_STYLES: Record<string, string> = {
    promotion: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    demotion:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    transfer:
        'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
    mutation:
        'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
    suspension:
        'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400',
    exit: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
    scheduled:
        'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-400',
    applied:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

const CLEARANCE_STATUS_STYLES: Record<string, string> = {
    pending:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    done: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    waived: 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
};

const SNAP_FIELDS: { key: keyof Snap; label: string }[] = [
    { key: 'position', label: 'Posisi' },
    { key: 'department', label: 'Departemen' },
    { key: 'job_grade', label: 'Grade' },
    { key: 'manager', label: 'Atasan' },
    { key: 'employment_type', label: 'Tipe Kerja' },
    { key: 'status', label: 'Status' },
];

export default function MovementsShow({ movement }: ShowProps) {
    useFlashToast();

    const isDraft = movement.status === 'draft';
    const isScheduled = movement.status === 'scheduled';
    const initials = movement.employee_name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    function handleApply() {
        router.post(
            movements.apply.url(movement.id),
            {},
            { preserveScroll: true },
        );
    }

    function handleSchedule() {
        router.post(
            movements.schedule.url(movement.id),
            {},
            { preserveScroll: true },
        );
    }

    function handleUnschedule() {
        router.post(
            movements.unschedule.url(movement.id),
            {},
            { preserveScroll: true },
        );
    }

    function handleDelete() {
        router.delete(movements.destroy.url(movement.id));
    }

    function handleClearance(itemId: number, status: 'done' | 'waived') {
        router.patch(
            movements.updateClearance.url(itemId),
            { status },
            { preserveScroll: true },
        );
    }

    const clearanceDone = movement.clearance
        ? movement.clearance.items.length - movement.clearance.pending_count
        : 0;

    return (
        <>
            <Head title={`Movement — ${movement.employee_name}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader title="Detail Movement">
                    <Button asChild variant="secondary">
                        <Link href={movements.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    {isDraft && movement.can_apply ? (
                        <ConfirmDialog
                            title="Terapkan Movement"
                            description="Terapkan movement ini? Data employment & status karyawan akan berubah."
                            confirmLabel="Terapkan"
                            onConfirm={handleApply}
                            trigger={
                                <Button variant="success">
                                    <CheckCircle />
                                    Terapkan Movement
                                </Button>
                            }
                        />
                    ) : null}
                    {movement.can_schedule ? (
                        <ConfirmDialog
                            title="Jadwalkan Movement"
                            description={`Jadwalkan movement ini? Akan diterapkan otomatis pada tanggal efektif ${formatDateID(movement.effective_date)}.`}
                            confirmLabel="Jadwalkan"
                            onConfirm={handleSchedule}
                            trigger={
                                <Button variant="secondary">
                                    <CalendarClock />
                                    Jadwalkan
                                </Button>
                            }
                        />
                    ) : null}
                    {movement.can_unschedule ? (
                        <ConfirmDialog
                            title="Batalkan Jadwal"
                            description="Batalkan jadwal movement ini? Movement akan kembali menjadi draft dan tidak diterapkan otomatis."
                            confirmLabel="Batalkan Jadwal"
                            onConfirm={handleUnschedule}
                            trigger={
                                <Button variant="secondary">
                                    <CalendarX />
                                    Batalkan Jadwal
                                </Button>
                            }
                        />
                    ) : null}
                    {isDraft ? (
                        <ConfirmDialog
                            title="Hapus Movement"
                            description={`Yakin hapus movement ${movement.type_label} untuk ${movement.employee_name}?`}
                            confirmLabel="Hapus"
                            onConfirm={handleDelete}
                            trigger={
                                <Button variant="destructive">
                                    <Trash2 />
                                    Hapus
                                </Button>
                            }
                        />
                    ) : null}
                </PageHeader>

                <InfoHero
                    initials={initials}
                    title={movement.employee_name}
                    subtitle={`${movement.employee_no} · Efektif ${formatDateID(movement.effective_date)}`}
                    badges={
                        <>
                            <Badge
                                variant="secondary"
                                className={TYPE_STYLES[movement.type]}
                            >
                                {movement.type_label}
                            </Badge>
                            <Badge
                                variant="secondary"
                                className={STATUS_STYLES[movement.status]}
                            >
                                {movement.status_label}
                            </Badge>
                        </>
                    }
                    aside={
                        <>
                            <p className="text-xs text-muted-foreground">
                                Tanggal Efektif
                            </p>
                            <p className="text-lg font-semibold text-navy">
                                {formatDateID(movement.effective_date)}
                            </p>
                            {isScheduled ? (
                                <p className="text-xs text-muted-foreground">
                                    Diterapkan otomatis
                                </p>
                            ) : null}
                        </>
                    }
                />

                <div className="grid gap-5 lg:grid-cols-2">
                    <SectionCard
                        title="Ringkasan"
                        icon={Repeat}
                        contentClassName="grid gap-3"
                    >
                        <DetailItem
                            label="Karyawan"
                            value={
                                <span>
                                    {movement.employee_name}
                                    <span className="ml-1 text-xs text-muted-foreground">
                                        ({movement.employee_no})
                                    </span>
                                </span>
                            }
                        />
                        <DetailItem
                            label="Tipe Movement"
                            value={
                                <Badge
                                    variant="secondary"
                                    className={TYPE_STYLES[movement.type]}
                                >
                                    {movement.type_label}
                                </Badge>
                            }
                        />
                        <DetailItem
                            label="Tanggal Efektif"
                            value={formatDateID(movement.effective_date)}
                        />
                        <DetailItem
                            label="Status"
                            value={
                                <span className="flex flex-col gap-1">
                                    <Badge
                                        variant="secondary"
                                        className={`w-fit ${STATUS_STYLES[movement.status]}`}
                                    >
                                        {movement.status_label}
                                    </Badge>
                                    {isScheduled ? (
                                        <span className="text-xs text-muted-foreground">
                                            Diterapkan otomatis pada{' '}
                                            {formatDateID(
                                                movement.effective_date,
                                            )}
                                        </span>
                                    ) : null}
                                </span>
                            }
                        />
                        <DetailItem label="Alasan" value={movement.reason} />
                        <DetailItem
                            label="Perlu Clearance"
                            value={movement.requires_clearance ? 'Ya' : 'Tidak'}
                        />
                        <DetailItem
                            label="Diterapkan pada"
                            value={
                                movement.applied_at
                                    ? formatDateID(movement.applied_at)
                                    : null
                            }
                        />
                        <DetailItem
                            label="Diterapkan oleh"
                            value={movement.applied_by}
                        />
                    </SectionCard>

                    <SectionCard
                        title="Perubahan (Before → After)"
                        icon={ArrowRight}
                        contentClassName="flex flex-col gap-2"
                    >
                        {SNAP_FIELDS.map((field) => {
                            const before = movement.before?.[field.key] ?? null;
                            const after = movement.after?.[field.key] ?? null;
                            const changed = before !== after;

                            return (
                                <div
                                    key={field.key}
                                    className={`grid grid-cols-[7rem_1fr] items-center gap-2 rounded-md px-2 py-2 ${
                                        changed
                                            ? 'bg-amber-50 dark:bg-amber-500/10'
                                            : ''
                                    }`}
                                >
                                    <span className="text-xs text-muted-foreground">
                                        {field.label}
                                    </span>
                                    <span className="flex items-center gap-1.5 text-sm">
                                        <span className="text-muted-foreground">
                                            {before ?? '—'}
                                        </span>
                                        <ArrowRight className="size-3.5 shrink-0 text-muted-foreground" />
                                        <span
                                            className={
                                                changed
                                                    ? 'font-semibold text-amber-700 dark:text-amber-400'
                                                    : 'text-foreground'
                                            }
                                        >
                                            {after ?? '—'}
                                        </span>
                                    </span>
                                </div>
                            );
                        })}
                    </SectionCard>

                    {movement.clearance && movement.clearance.required ? (
                        <SectionCard
                            title="Clearance / Exit"
                            icon={ShieldCheck}
                            description="Selesaikan semua item sebelum movement dapat diterapkan."
                            className="lg:col-span-2"
                            actions={
                                <Badge
                                    variant="secondary"
                                    className={
                                        movement.clearance.pending_count === 0
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400'
                                    }
                                >
                                    {clearanceDone}/
                                    {movement.clearance.items.length} selesai
                                </Badge>
                            }
                            contentClassName="flex flex-col gap-2"
                        >
                            {movement.clearance.items.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex flex-col gap-2 rounded-md border border-border/50 px-3 py-3 last:mb-0 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div className="flex flex-col gap-1">
                                        <span className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                                            {item.category}
                                        </span>
                                        <span className="text-sm font-medium text-foreground">
                                            {item.label}
                                        </span>
                                        {item.note ? (
                                            <span className="text-xs text-muted-foreground">
                                                {item.note}
                                            </span>
                                        ) : null}
                                        {item.status !== 'pending' &&
                                        item.completed_by ? (
                                            <span className="text-xs text-muted-foreground">
                                                oleh {item.completed_by}
                                                {item.completed_at
                                                    ? ` · ${formatDateID(item.completed_at)}`
                                                    : ''}
                                            </span>
                                        ) : null}
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        <Badge
                                            variant="secondary"
                                            className={
                                                CLEARANCE_STATUS_STYLES[
                                                    item.status
                                                ]
                                            }
                                        >
                                            {item.status_label}
                                        </Badge>
                                        {isDraft &&
                                        item.status === 'pending' ? (
                                            <>
                                                <Button
                                                    size="sm"
                                                    variant="success"
                                                    onClick={() =>
                                                        handleClearance(
                                                            item.id,
                                                            'done',
                                                        )
                                                    }
                                                >
                                                    <CheckCircle />
                                                    Selesai
                                                </Button>
                                                <ConfirmDialog
                                                    title="Waive Item"
                                                    description={`Kecualikan item "${item.label}" dari clearance?`}
                                                    confirmLabel="Waive"
                                                    onConfirm={() =>
                                                        handleClearance(
                                                            item.id,
                                                            'waived',
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            size="sm"
                                                            variant="secondary"
                                                        >
                                                            <MinusCircle />
                                                            Waive
                                                        </Button>
                                                    }
                                                />
                                            </>
                                        ) : null}
                                    </div>
                                </div>
                            ))}
                        </SectionCard>
                    ) : null}
                </div>
            </div>
        </>
    );
}
