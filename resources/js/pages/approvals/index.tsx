import { Head, Link } from '@inertiajs/react';
import { Inbox } from 'lucide-react';
import approvals from '@/actions/App/Http/Controllers/ApprovalController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFlashToast } from '@/hooks/use-flash-toast';

type ApprovalRow = {
    id: number;
    type: string;
    title: string;
    requester: string | null;
    status: string;
    current_step: number;
    total_steps: number;
    submitted_at: string | null;
};

type IndexProps = {
    requests: ApprovalRow[];
};

const TYPE_LABELS: Record<string, string> = {
    overtime: 'Lembur',
    leave: 'Cuti',
    reimbursement: 'Reimbursement',
    loan: 'Pinjaman',
    work_visit: 'Kunjungan Kerja',
    benefit_claim: 'Klaim Benefit',
    attendance_correction: 'Koreksi Absensi',
    employee_change: 'Perubahan Data Karyawan',
    lifecycle: 'Perubahan Data',
    payroll: 'Payroll',
};

export default function ApprovalsIndex({ requests }: IndexProps) {
    useFlashToast();

    return (
        <>
            <Head title="Inbox Approval" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Inbox Approval"
                    description="Pengajuan yang menunggu keputusan Anda."
                />

                <Card className="gap-0 py-0">
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12">No</TableHead>
                                    <TableHead>Transaksi</TableHead>
                                    <TableHead>Pemohon</TableHead>
                                    <TableHead>Langkah</TableHead>
                                    <TableHead>Diajukan</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {requests.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-12 text-center text-muted-foreground"
                                        >
                                            <Inbox className="mx-auto mb-2 size-8 opacity-40" />
                                            Tidak ada pengajuan menunggu
                                            persetujuan Anda.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    requests.map((item, index) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="text-muted-foreground tabular-nums">
                                                {index + 1}
                                            </TableCell>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {item.title}
                                                </div>
                                                <Badge
                                                    variant="secondary"
                                                    className="mt-1"
                                                >
                                                    {TYPE_LABELS[item.type] ??
                                                        item.type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {item.requester ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {item.current_step}/
                                                {item.total_steps}
                                            </TableCell>
                                            <TableCell>
                                                {item.submitted_at ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="warning"
                                                >
                                                    <Link
                                                        href={approvals.show.url(
                                                            item.id,
                                                        )}
                                                    >
                                                        Tinjau
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
