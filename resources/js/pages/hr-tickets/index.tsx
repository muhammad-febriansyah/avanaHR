import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Eye,
    LifeBuoy,
    Plus,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import hrTickets from '@/actions/App/Http/Controllers/HrTicketController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFlashToast } from '@/hooks/use-flash-toast';
import type { Paginator } from '@/types/employee';

type Option = { value: string; label: string };
type EmployeeOption = { id: number; label: string };

type TicketRow = {
    id: number;
    ticket_no: string;
    subject: string;
    category: string;
    status: string;
    priority: string;
    employee_name: string | null;
    employee_no: string | null;
    messages_count: number;
    created_at: string | null;
};

type Filters = { search?: string; status?: string; priority?: string };

type IndexProps = {
    tickets: Paginator<TicketRow>;
    filters: Filters;
    statuses: Option[];
    priorities: Option[];
    categories: Option[];
    options: { employees: EmployeeOption[] };
};

const ALL = 'all';

export const STATUS_STYLES: Record<string, string> = {
    open: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    resolved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    closed: 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
};

export const PRIORITY_STYLES: Record<string, string> = {
    low: 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
    medium: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    high: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    urgent: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
};

function label(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

type TicketForm = {
    subject: string;
    category: string;
    priority: string;
    employee_id: string;
    body: string;
};

const emptyForm: TicketForm = {
    subject: '',
    category: 'general',
    priority: 'medium',
    employee_id: '',
    body: '',
};

export default function HrTicketsIndex({
    tickets: paginator,
    filters = {},
    statuses,
    priorities,
    categories,
    options,
}: IndexProps) {
    useFlashToast();

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? ALL);
    const [priority, setPriority] = useState(filters.priority ?? ALL);
    const firstRender = useRef(true);

    function go(extra: Record<string, string | undefined>) {
        router.get(
            hrTickets.index.url(),
            {
                search: search || undefined,
                status: status === ALL ? undefined : status,
                priority: priority === ALL ? undefined : priority,
                ...extra,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = setTimeout(() => go({}), 350);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const [open, setOpen] = useState(false);
    const form = useForm<TicketForm>(emptyForm);

    function openCreate() {
        form.setDefaults(emptyForm);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        form.post(hrTickets.store.url(), { onSuccess: () => setOpen(false) });
    }

    const rows = paginator.data;

    return (
        <>
            <Head title="Tiket HR" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tiket HR"
                    description="Helpdesk internal: pertanyaan & permintaan karyawan ke tim HR."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Buat Tiket
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="flex flex-col gap-4 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari subjek / no tiket"
                                className="sm:max-w-xs"
                            />
                            <Select
                                value={status}
                                onValueChange={(value) => {
                                    setStatus(value);
                                    go({ status: value === ALL ? undefined : value });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Semua Status</SelectItem>
                                    {statuses.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={priority}
                                onValueChange={(value) => {
                                    setPriority(value);
                                    go({ priority: value === ALL ? undefined : value });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="Semua Prioritas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Semua Prioritas</SelectItem>
                                    {priorities.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>No. Tiket</TableHead>
                                        <TableHead>Subjek</TableHead>
                                        <TableHead>Karyawan</TableHead>
                                        <TableHead>Prioritas</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-12">
                                                <div className="flex flex-col items-center justify-center gap-3 text-center">
                                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <LifeBuoy className="size-6" />
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        Belum ada tiket
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="font-mono text-xs">
                                                    {row.ticket_no}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {row.subject}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {label(categories, row.category)} ·{' '}
                                                        {row.messages_count} pesan
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm">
                                                        {row.employee_name ?? '-'}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {row.employee_no}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={PRIORITY_STYLES[row.priority]}
                                                    >
                                                        {label(priorities, row.priority)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className={STATUS_STYLES[row.status]}
                                                    >
                                                        {label(statuses, row.status)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <Link
                                                            href={hrTickets.show.url(row.id)}
                                                        >
                                                            <Eye />
                                                            Lihat
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                {paginator.total} tiket
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!paginator.prev_page_url}
                                    onClick={() =>
                                        paginator.prev_page_url &&
                                        router.get(
                                            paginator.prev_page_url,
                                            {},
                                            { preserveState: true, preserveScroll: true },
                                        )
                                    }
                                >
                                    <ChevronLeft />
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!paginator.next_page_url}
                                    onClick={() =>
                                        paginator.next_page_url &&
                                        router.get(
                                            paginator.next_page_url,
                                            {},
                                            { preserveState: true, preserveScroll: true },
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

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Buat Tiket HR</DialogTitle>
                    </DialogHeader>

                    <form id="ticket-form" onSubmit={handleSubmit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="tk-subject">Subjek</Label>
                            <Input
                                id="tk-subject"
                                value={form.data.subject}
                                onChange={(e) => form.setData('subject', e.target.value)}
                                placeholder="Mis. Koreksi data NPWP"
                            />
                            {form.errors.subject && (
                                <p className="text-sm text-destructive">
                                    {form.errors.subject}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="tk-category">Kategori</Label>
                                <Select
                                    value={form.data.category}
                                    onValueChange={(value) =>
                                        form.setData('category', value)
                                    }
                                >
                                    <SelectTrigger id="tk-category" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((option) => (
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
                                <Label htmlFor="tk-priority">Prioritas</Label>
                                <Select
                                    value={form.data.priority}
                                    onValueChange={(value) =>
                                        form.setData('priority', value)
                                    }
                                >
                                    <SelectTrigger id="tk-priority" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {priorities.map((option) => (
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
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="tk-employee">Karyawan (opsional)</Label>
                            <Select
                                value={form.data.employee_id}
                                onValueChange={(value) =>
                                    form.setData('employee_id', value)
                                }
                            >
                                <SelectTrigger id="tk-employee" className="w-full">
                                    <SelectValue placeholder="Pilih karyawan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.employees.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={String(option.id)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="tk-body">Deskripsi</Label>
                            <textarea
                                id="tk-body"
                                value={form.data.body}
                                onChange={(e) => form.setData('body', e.target.value)}
                                rows={4}
                                placeholder="Jelaskan detail permintaan…"
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            />
                            {form.errors.body && (
                                <p className="text-sm text-destructive">
                                    {form.errors.body}
                                </p>
                            )}
                        </div>
                    </form>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setOpen(false)}
                        >
                            <X />
                            Batal
                        </Button>
                        <Button type="submit" form="ticket-form" disabled={form.processing}>
                            Buat Tiket
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
