import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Banknote,
    CalendarRange,
    Car,
    ClipboardList,
    FileText,
    MapPin,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import type { FormEvent } from 'react';
import workVisits from '@/actions/App/Http/Controllers/WorkVisitController';
import ConfirmDialog from '@/components/confirm-dialog';
import { DetailItem } from '@/components/detail/detail-item';
import { InfoHero } from '@/components/detail/info-hero';
import { SectionCard } from '@/components/detail/section-card';
import { StatTile } from '@/components/detail/stat-tile';
import FileDropzone from '@/components/file-dropzone';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatDateID, formatDateTimeID, formatRupiah } from '@/lib/format';

type Report = {
    id: number;
    visited_at: string;
    location: string;
    notes: string | null;
    attachment_path: string | null;
    attachment_url: string | null;
};

type WorkVisit = {
    id: number;
    employee_name: string;
    employee_no: string;
    destination: string;
    purpose: string;
    start_date: string;
    end_date: string;
    transport_mode: string | null;
    estimated_cost: number | null;
    status: string;
    status_label: string;
    notes: string | null;
    decided_by: string | null;
    decided_at: string | null;
    decision_note: string | null;
    can_decide: boolean;
    can_edit: boolean;
    reports: Report[];
};

type ShowProps = {
    workVisit: WorkVisit;
};

const STATUS_STYLES: Record<string, string> = {
    pending:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    approved:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    cancelled:
        'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
};

type ReportForm = {
    visited_at: string;
    location: string;
    notes: string;
    attachment_path: string;
    attachment: File | null;
};

const emptyReport: ReportForm = {
    visited_at: '',
    location: '',
    notes: '',
    attachment_path: '',
    attachment: null,
};

/** Inclusive day count between two ISO dates. */
function durationDays(start: string, end: string): string {
    const a = new Date(start);
    const b = new Date(end);

    if (Number.isNaN(a.getTime()) || Number.isNaN(b.getTime())) {
        return '—';
    }

    const days =
        Math.round((b.getTime() - a.getTime()) / (1000 * 60 * 60 * 24)) + 1;

    return `${days} hari`;
}

export default function WorkVisitsShow({ workVisit }: ShowProps) {
    useFlashToast();

    const reportForm = useForm<ReportForm>(emptyReport);

    function handleDelete() {
        router.delete(workVisits.destroy.url(workVisit.id));
    }

    function submitReport(event: FormEvent) {
        event.preventDefault();
        reportForm.post(workVisits.storeReport.url(workVisit.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => reportForm.reset(),
        });
    }

    function deleteReport(reportId: number) {
        router.delete(
            workVisits.destroyReport.url({
                workVisit: workVisit.id,
                report: reportId,
            }),
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={`Kunjungan — ${workVisit.destination}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Detail Kunjungan Kerja"
                    description="Persetujuan diproses melalui Inbox Approval."
                >
                    <Button asChild variant="secondary">
                        <Link href={workVisits.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    {workVisit.can_edit ? (
                        <>
                            <Button asChild variant="success">
                                <Link href={workVisits.edit.url(workVisit.id)}>
                                    <Pencil />
                                    Edit
                                </Link>
                            </Button>
                            <ConfirmDialog
                                title="Hapus Kunjungan Kerja"
                                description={`Yakin ingin menghapus kunjungan kerja "${workVisit.destination}"?`}
                                confirmLabel="Hapus"
                                onConfirm={handleDelete}
                                trigger={
                                    <Button variant="destructive">
                                        <Trash2 />
                                        Hapus
                                    </Button>
                                }
                            />
                        </>
                    ) : null}
                </PageHeader>

                <InfoHero
                    icon={MapPin}
                    title={workVisit.destination}
                    subtitle={`${workVisit.employee_name} · ${workVisit.employee_no}`}
                    badges={
                        <Badge
                            variant="secondary"
                            className={STATUS_STYLES[workVisit.status]}
                        >
                            {workVisit.status_label}
                        </Badge>
                    }
                    aside={
                        <>
                            <p className="text-xs text-muted-foreground">
                                Periode
                            </p>
                            <p className="text-sm font-semibold text-navy">
                                {formatDateID(workVisit.start_date)} –{' '}
                                {formatDateID(workVisit.end_date)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {durationDays(
                                    workVisit.start_date,
                                    workVisit.end_date,
                                )}
                            </p>
                        </>
                    }
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatTile
                        label="Durasi"
                        value={durationDays(
                            workVisit.start_date,
                            workVisit.end_date,
                        )}
                        icon={CalendarRange}
                        accent="blue"
                    />
                    <StatTile
                        label="Transportasi"
                        value={workVisit.transport_mode ?? '—'}
                        icon={Car}
                        accent="violet"
                    />
                    <StatTile
                        label="Estimasi Biaya"
                        value={
                            workVisit.estimated_cost !== null
                                ? formatRupiah(workVisit.estimated_cost)
                                : '—'
                        }
                        icon={Banknote}
                        accent="amber"
                    />
                    <StatTile
                        label="Laporan"
                        value={workVisit.reports.length}
                        icon={FileText}
                        accent="green"
                    />
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <div className="flex flex-col gap-5">
                        <SectionCard
                            title="Ringkasan"
                            icon={ClipboardList}
                            contentClassName="grid gap-3"
                        >
                            <DetailItem
                                label="Karyawan"
                                value={
                                    <span>
                                        {workVisit.employee_name}
                                        <span className="ml-1 text-xs text-muted-foreground">
                                            ({workVisit.employee_no})
                                        </span>
                                    </span>
                                }
                            />
                            <DetailItem
                                label="Tujuan"
                                value={workVisit.destination}
                                icon={MapPin}
                            />
                            <DetailItem
                                label="Keperluan"
                                value={workVisit.purpose}
                            />
                            <DetailItem
                                label="Tanggal"
                                value={`${formatDateID(workVisit.start_date)} – ${formatDateID(workVisit.end_date)}`}
                                icon={CalendarRange}
                            />
                            <DetailItem
                                label="Transportasi"
                                value={workVisit.transport_mode}
                                icon={Car}
                            />
                            <DetailItem
                                label="Estimasi Biaya"
                                value={
                                    workVisit.estimated_cost !== null
                                        ? formatRupiah(workVisit.estimated_cost)
                                        : null
                                }
                                icon={Banknote}
                            />
                            <DetailItem
                                label="Catatan"
                                value={workVisit.notes}
                            />
                        </SectionCard>

                        {workVisit.decided_by ||
                        workVisit.decided_at ||
                        workVisit.decision_note ? (
                            <SectionCard
                                title="Keputusan"
                                icon={ClipboardList}
                                contentClassName="grid gap-3"
                            >
                                <DetailItem
                                    label="Diputuskan oleh"
                                    value={workVisit.decided_by}
                                />
                                <DetailItem
                                    label="Diputuskan pada"
                                    value={
                                        workVisit.decided_at
                                            ? formatDateTimeID(
                                                  workVisit.decided_at,
                                              )
                                            : null
                                    }
                                />
                                <DetailItem
                                    label="Alasan keputusan"
                                    value={workVisit.decision_note}
                                />
                            </SectionCard>
                        ) : null}
                    </div>

                    <SectionCard
                        title="Laporan Kunjungan"
                        icon={FileText}
                        actions={
                            <span className="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                {workVisit.reports.length}
                            </span>
                        }
                        contentClassName="flex flex-col gap-4"
                    >
                        <div className="flex flex-col gap-2">
                            {workVisit.reports.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada laporan kunjungan.
                                </p>
                            ) : (
                                workVisit.reports.map((report) => (
                                    <div
                                        key={report.id}
                                        className="flex flex-col gap-2 rounded-md border border-border/50 px-3 py-3 sm:flex-row sm:items-start sm:justify-between"
                                    >
                                        <div className="flex flex-col gap-1">
                                            <span className="text-sm font-medium text-foreground">
                                                {report.location}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {formatDateID(
                                                    report.visited_at,
                                                )}
                                            </span>
                                            {report.notes ? (
                                                <span className="text-xs text-muted-foreground">
                                                    {report.notes}
                                                </span>
                                            ) : null}
                                            {report.attachment_url ? (
                                                <a
                                                    href={report.attachment_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-xs font-medium text-primary underline-offset-2 hover:underline"
                                                >
                                                    Lihat bukti / foto
                                                </a>
                                            ) : null}
                                        </div>
                                        <ConfirmDialog
                                            title="Hapus Laporan"
                                            description={`Yakin ingin menghapus laporan kunjungan di "${report.location}"?`}
                                            confirmLabel="Hapus"
                                            onConfirm={() =>
                                                deleteReport(report.id)
                                            }
                                            trigger={
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    className="shrink-0"
                                                >
                                                    <Trash2 />
                                                    Hapus
                                                </Button>
                                            }
                                        />
                                    </div>
                                ))
                            )}
                        </div>

                        <form
                            onSubmit={submitReport}
                            className="flex flex-col gap-3 border-t border-border/50 pt-4"
                        >
                            <p className="text-sm font-semibold text-navy">
                                Tambah Laporan
                            </p>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="visited_at">
                                        Tanggal Kunjungan
                                    </Label>
                                    <DatePicker
                                        id="visited_at"
                                        value={reportForm.data.visited_at}
                                        onChange={(value) =>
                                            reportForm.setData(
                                                'visited_at',
                                                value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={reportForm.errors.visited_at}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="location">Lokasi</Label>
                                    <Input
                                        id="location"
                                        value={reportForm.data.location}
                                        onChange={(e) =>
                                            reportForm.setData(
                                                'location',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Mis. Kantor Cabang Surabaya"
                                    />
                                    <InputError
                                        message={reportForm.errors.location}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="report_notes">Catatan</Label>
                                <textarea
                                    id="report_notes"
                                    value={reportForm.data.notes}
                                    onChange={(e) =>
                                        reportForm.setData(
                                            'notes',
                                            e.target.value,
                                        )
                                    }
                                    rows={2}
                                    placeholder="Catatan kunjungan (opsional)"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                />
                                <InputError message={reportForm.errors.notes} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="attachment_path">
                                    Bukti / Foto (Link)
                                </Label>
                                <Input
                                    id="attachment_path"
                                    value={reportForm.data.attachment_path}
                                    onChange={(e) =>
                                        reportForm.setData(
                                            'attachment_path',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Link bukti / foto (opsional)"
                                />
                                <InputError
                                    message={reportForm.errors.attachment_path}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="attachment">
                                    Unggah Berkas (opsional)
                                </Label>
                                <FileDropzone
                                    id="attachment"
                                    value={reportForm.data.attachment}
                                    onChange={(file) =>
                                        reportForm.setData('attachment', file)
                                    }
                                    accept=".pdf,.png,.jpg,.jpeg,.webp"
                                    hint="PDF/PNG/JPG/WEBP · maks 4 MB · mengganti link bila diunggah"
                                    variant="file"
                                />
                                <InputError
                                    message={reportForm.errors.attachment}
                                />
                            </div>
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={reportForm.processing}
                                >
                                    <Plus />
                                    Tambah Laporan
                                </Button>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            </div>
        </>
    );
}
