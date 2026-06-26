import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Clock, MessageSquare, Send, Tag, User } from 'lucide-react';
import type { FormEvent } from 'react';
import hrTickets from '@/actions/App/Http/Controllers/HrTicketController';
import { DetailItem } from '@/components/detail/detail-item';
import { SectionCard } from '@/components/detail/section-card';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { PRIORITY_STYLES, STATUS_STYLES } from '@/pages/hr-tickets/index';

type Option = { value: string; label: string };

type Message = {
    id: number;
    body: string;
    author: string;
    created_at: string | null;
};

type Ticket = {
    id: number;
    ticket_no: string;
    subject: string;
    category: string;
    status: string;
    priority: string;
    employee_name: string | null;
    employee_no: string | null;
    assigned_to: string | null;
    sla_due_at: string | null;
    created_at: string | null;
    messages: Message[];
};

type ShowProps = {
    ticket: Ticket;
    statuses: Option[];
    priorities: Option[];
};

function label(options: Option[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

function initialsOf(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

export default function HrTicketShow({
    ticket,
    statuses,
    priorities,
}: ShowProps) {
    useFlashToast();

    const reply = useForm({ body: '' });
    const meta = useForm({ status: ticket.status, priority: ticket.priority });

    function sendReply(event: FormEvent) {
        event.preventDefault();
        reply.post(hrTickets.reply.url(ticket.id), {
            preserveScroll: true,
            onSuccess: () => reply.reset(),
        });
    }

    function saveMeta(next: Partial<{ status: string; priority: string }>) {
        const payload = { ...meta.data, ...next };
        meta.setData(payload as { status: string; priority: string });
        router.patch(hrTickets.update.url(ticket.id), payload, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title={`Tiket ${ticket.ticket_no}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={ticket.subject}
                    description={ticket.ticket_no}
                >
                    <Button asChild variant="secondary">
                        <Link href={hrTickets.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <div className="grid gap-5 lg:grid-cols-[1fr_320px]">
                    <SectionCard
                        title="Percakapan"
                        icon={MessageSquare}
                        actions={
                            <span className="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                {ticket.messages.length} pesan
                            </span>
                        }
                        contentClassName="flex flex-col gap-4"
                    >
                        <div className="flex flex-col gap-4">
                            {ticket.messages.map((message) => (
                                <div key={message.id} className="flex gap-3">
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-[linear-gradient(135deg,#2F54C9,#6E9BE6)] text-xs font-semibold text-white">
                                        {initialsOf(message.author)}
                                    </span>
                                    <div className="min-w-0 flex-1 rounded-lg rounded-tl-none border bg-muted/30 p-3">
                                        <div className="mb-1 flex items-center justify-between gap-2">
                                            <span className="text-sm font-medium text-navy">
                                                {message.author}
                                            </span>
                                            <span className="shrink-0 text-xs text-muted-foreground tabular-nums">
                                                {message.created_at}
                                            </span>
                                        </div>
                                        <p className="text-sm whitespace-pre-wrap text-foreground">
                                            {message.body}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <form
                            onSubmit={sendReply}
                            className="flex flex-col gap-2 border-t pt-4"
                        >
                            <Label htmlFor="reply-body">Balas Tiket</Label>
                            <textarea
                                id="reply-body"
                                value={reply.data.body}
                                onChange={(e) =>
                                    reply.setData('body', e.target.value)
                                }
                                rows={3}
                                placeholder="Tulis balasan…"
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            />
                            {reply.errors.body ? (
                                <p className="text-sm text-destructive">
                                    {reply.errors.body}
                                </p>
                            ) : null}
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    disabled={reply.processing}
                                >
                                    <Send />
                                    Kirim Balasan
                                </Button>
                            </div>
                        </form>
                    </SectionCard>

                    <div className="flex flex-col gap-5">
                        <SectionCard
                            title="Detail Tiket"
                            icon={Tag}
                            className="h-fit"
                            contentClassName="grid gap-3"
                        >
                            <DetailItem
                                label="Status"
                                value={
                                    <Badge
                                        variant="secondary"
                                        className={STATUS_STYLES[ticket.status]}
                                    >
                                        {label(statuses, ticket.status)}
                                    </Badge>
                                }
                            />
                            <DetailItem
                                label="Prioritas"
                                value={
                                    <Badge
                                        variant="secondary"
                                        className={
                                            PRIORITY_STYLES[ticket.priority]
                                        }
                                    >
                                        {label(priorities, ticket.priority)}
                                    </Badge>
                                }
                            />
                            <DetailItem
                                label="Kategori"
                                value={ticket.category}
                                icon={Tag}
                            />
                            <DetailItem
                                label="Karyawan"
                                value={
                                    ticket.employee_name
                                        ? `${ticket.employee_name}${ticket.employee_no ? ` (${ticket.employee_no})` : ''}`
                                        : null
                                }
                                icon={User}
                            />
                            <DetailItem
                                label="Ditangani"
                                value={ticket.assigned_to}
                                icon={User}
                            />
                            <DetailItem
                                label="SLA"
                                value={ticket.sla_due_at}
                                icon={Clock}
                            />
                            <DetailItem
                                label="Dibuat"
                                value={ticket.created_at}
                                icon={Clock}
                            />
                        </SectionCard>

                        <SectionCard
                            title="Kelola"
                            className="h-fit"
                            contentClassName="grid gap-4"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="meta-status">Ubah Status</Label>
                                <Select
                                    value={meta.data.status}
                                    onValueChange={(value) =>
                                        saveMeta({ status: value })
                                    }
                                >
                                    <SelectTrigger
                                        id="meta-status"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((option) => (
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
                                <Label htmlFor="meta-priority">
                                    Ubah Prioritas
                                </Label>
                                <Select
                                    value={meta.data.priority}
                                    onValueChange={(value) =>
                                        saveMeta({ priority: value })
                                    }
                                >
                                    <SelectTrigger
                                        id="meta-priority"
                                        className="w-full"
                                    >
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
                        </SectionCard>
                    </div>
                </div>
            </div>
        </>
    );
}
