import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Eye, Plus, Wallet } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import employeeBenefits from '@/actions/App/Http/Controllers/EmployeeBenefitController';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatRupiah } from '@/lib/format';
import type { Paginator } from '@/types/employee';

type Row = {
    id: number;
    employee_name: string;
    employee_no: string;
    benefit_type_name: string;
    period_year: number;
    quota: number;
    used: number;
    remaining: number;
};

type Filters = { search?: string; period_year?: string };

type IndexProps = {
    employeeBenefits: Paginator<Row>;
    filters: Filters;
};

export default function EmployeeBenefitsIndex({
    employeeBenefits: paginator,
    filters = {},
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [periodYear, setPeriodYear] = useState(filters.period_year ?? '');
    const firstRender = useRef(true);

    function go() {
        router.get(
            employeeBenefits.index.url(),
            {
                search: search || undefined,
                period_year: periodYear || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = setTimeout(() => go(), 350);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search, periodYear]);

    const rows = paginator.data;

    return (
        <>
            <Head title="Benefit Karyawan" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Benefit Karyawan"
                    description="Kelola plafon benefit yang ditetapkan untuk setiap karyawan."
                >
                    <Button asChild>
                        <Link href={employeeBenefits.create.url()}>
                            <Plus />
                            Tetapkan Benefit
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Cari nama / NIP / jenis benefit"
                                className="sm:max-w-xs"
                            />
                            <Input
                                type="number"
                                value={periodYear}
                                onChange={(event) =>
                                    setPeriodYear(event.target.value)
                                }
                                placeholder="Tahun"
                                className="sm:w-32"
                            />
                        </div>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            No
                                        </TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead>Tahun</TableHead>
                                        <TableHead>Plafon</TableHead>
                                        <TableHead>Terpakai</TableHead>
                                        <TableHead>Sisa</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={8}
                                                className="py-12"
                                            >
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <Wallet className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada benefit
                                                        karyawan
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((item, index) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="text-muted-foreground tabular-nums">
                                                    {(paginator.from ?? 1) +
                                                        index}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {item.employee_name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {item.benefit_type_name}
                                                </TableCell>
                                                <TableCell>
                                                    {item.period_year}
                                                </TableCell>
                                                <TableCell>
                                                    {formatRupiah(item.quota)}
                                                </TableCell>
                                                <TableCell>
                                                    {formatRupiah(item.used)}
                                                </TableCell>
                                                <TableCell
                                                    className={
                                                        item.remaining > 0
                                                            ? 'font-medium text-emerald-600 dark:text-emerald-400'
                                                            : 'font-medium text-red-600 dark:text-red-400'
                                                    }
                                                >
                                                    {formatRupiah(
                                                        item.remaining,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="warning"
                                                        >
                                                            <Link
                                                                href={employeeBenefits.show.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Eye />
                                                                Detail
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} benefit
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={!paginator.prev_page_url}
                                    onClick={() =>
                                        paginator.prev_page_url &&
                                        router.get(
                                            paginator.prev_page_url,
                                            {},
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
                                        )
                                    }
                                >
                                    <ChevronLeft />
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={!paginator.next_page_url}
                                    onClick={() =>
                                        paginator.next_page_url &&
                                        router.get(
                                            paginator.next_page_url,
                                            {},
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
                                        )
                                    }
                                >
                                    Berikutnya
                                    <ChevronRight />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
