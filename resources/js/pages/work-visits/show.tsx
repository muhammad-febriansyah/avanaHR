import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import workVisits from '@/actions/App/Http/Controllers/WorkVisitController';
import ConfirmDialog from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
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

type Report = {
    id: number;
    visited_at: string;
    location: string;
    notes: string | null;
    attachment_path: string | null;
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
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    cancelled: 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
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

type ReportForm = {
    visited_at: string;
    location: string;
    notes: string;
    attachment_path: string;
};

const emptyReport: ReportForm = {
    visited_at: '',
    location: '',
    notes: '',
    attachment_path: '',
};

export default function WorkVisitsShow({ workVisit }: ShowProps) {
    useFlashToast();

    const [rejectOpen, setRejectOpen] = useState(false);
    const rejectForm = useForm<{ status: string; decision_note: string }>({
        status: 'rejected',
        decision_note: '',
    });

    const reportForm = useForm<ReportForm>(emptyReport);

    function approve() {
        router.patch(
            workVisits.decide.url(workVisit.id),
            { status: 'approved' },
            { preserveScroll: true },
        );
    }

    function submitReject(event: FormEvent) {
        event.preventDefault();
        rejectForm.patch(workVisits.decide.url(workVisit.id), {
            preserveScroll: true,
            onSuccess: () => {
                rejectForm.reset();
                setRejectOpen(false);
            },
        });
    }

    function handleDelete() {
        router.delete(workVisits.destroy.url(workVisit.id));
    }

    function submitReport(event: FormEvent) {
        event.preventDefault();
        reportForm.post(workVisits.storeReport.url(workVisit.id), {
            preserveScroll: true,
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
                <PageHeader title={`Kunjungan — ${workVisit.destination}`}>
                    <Button asChild variant="outline">
                        <Link href={workVisits.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    {workVisit.can_decide && (
                        <>
                            <Button variant="success" onClick={approve}>
                                <CheckCircle />
                                Setujui
                            </Button>
                            <Button
                                variant="outline"
                                className="text-red-700 hover:text-red-700 dark:text-red-400"
                                onClick={() => setRejectOpen(true)}
                            >
                                <X />
                                Tolak
                            </Button>
                        </>
                    )}
                    {workVisit.can_edit && (
                        <>
                            <Button asChild variant="outline">
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
                    )}
                </PageHeader>

                <div className="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Ringkasan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            <DetailRow
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
                            <DetailRow
                                label="Tujuan"
                                value={workVisit.destination}
                            />
                            <DetailRow
                                label="Keperluan"
                                value={workVisit.purpose}
                            />
                            <DetailRow
                                label="Tanggal"
                                value={`${formatDateID(workVisit.start_date)} – ${formatDateID(workVisit.end_date)}`}
                            />
                            <DetailRow
                                label="Transportasi"
                                value={workVisit.transport_mode ?? '-'}
                            />
                            <DetailRow
                                label="Estimasi Biaya"
                                value={
                                    workVisit.estimated_cost !== null
                                        ? formatRupiah(workVisit.estimated_cost)
                                        : '-'
                                }
                            />
                            <DetailRow
                                label="Status"
                                value={
                                    <Badge
                                        variant="secondary"
                                        className={
                                            STATUS_STYLES[workVisit.status]
                                        }
                                    >
                                        {workVisit.status_label}
                                    </Badge>
                                }
                            />
                            <DetailRow
                                label="Catatan"
                                value={workVisit.notes ?? '-'}
                            />
                            <DetailRow
                                label="Diputuskan oleh"
                                value={workVisit.decided_by ?? '-'}
                            />
                            <DetailRow
                                label="Diputuskan pada"
                                value={
                                    workVisit.decided_at
                                        ? formatDateTimeID(workVisit.decided_at)
                                        : '-'
                                }
                            />
                            <DetailRow
                                label="Alasan keputusan"
                                value={workVisit.decision_note ?? '-'}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Laporan Kunjungan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
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
                                                {report.notes && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {report.notes}
                                                    </span>
                                                )}
                                                {report.attachment_path && (
                                                    <a
                                                        href={
                                                            report.attachment_path
                                                        }
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-xs font-medium text-primary underline-offset-2 hover:underline"
                                                    >
                                                        Lihat bukti / foto
                                                    </a>
                                                )}
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
                                                        variant="outline"
                                                        className="shrink-0 text-red-700 hover:text-red-700 dark:text-red-400"
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
                                        <Input
                                            id="visited_at"
                                            type="date"
                                            value={reportForm.data.visited_at}
                                            onChange={(e) =>
                                                reportForm.setData(
                                                    'visited_at',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Pilih tanggal"
                                        />
                                        <InputError
                                            message={
                                                reportForm.errors.visited_at
                                            }
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
                                        className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <InputError
                                        message={reportForm.errors.notes}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="attachment_path">
                                        Bukti / Foto
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
                                        message={
                                            reportForm.errors.attachment_path
                                        }
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
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tolak Kunjungan Kerja</DialogTitle>
                    </DialogHeader>

                    <form
                        id="reject-form"
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
                            onClick={() => setRejectOpen(false)}
                        >
                            <X />
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="reject-form"
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
