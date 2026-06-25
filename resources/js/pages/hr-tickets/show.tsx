import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, MessageSquare, Send } from 'lucide-react';
import type { FormEvent } from 'react';
import hrTickets from '@/actions/App/Http/Controllers/HrTicketController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

export default function HrTicketShow({ ticket, statuses, priorities }: ShowProps) {
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
                <PageHeader title={ticket.subject} description={ticket.ticket_no}>
                    <Button asChild variant="outline">
                        <a href={hrTickets.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </a>
                    </Button>
                </PageHeader>

                <div className="grid gap-5 lg:grid-cols-[1fr_320px]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <MessageSquare className="size-4" />
                                Percakapan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <div className="flex flex-col gap-4">
                                {ticket.messages.map((message) => (
                                    <div
                                        key={message.id}
                                        className="rounded-lg border bg-muted/30 p-3"
                                    >
                                        <div className="mb-1 flex items-center justify-between">
                                            <span className="text-sm font-medium text-navy">
                                                {message.author}
                                            </span>
                                            <span className="text-xs text-muted-foreground tabular-nums">
                                                {message.created_at}
                                            </span>
                                        </div>
                                        <p className="text-sm whitespace-pre-wrap text-foreground">
                                            {message.body}
                                        </p>
                                    </div>
                                ))}
                            </div>

                            <form
                                onSubmit={sendReply}
                                className="flex flex-col gap-2 border-t pt-4"
                            >
                                <textarea
                                    value={reply.data.body}
                                    onChange={(e) => reply.setData('body', e.target.value)}
                                    rows={3}
                                    placeholder="Tulis balasan…"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                />
                                {reply.errors.body && (
                                    <p className="text-sm text-destructive">
                                        {reply.errors.body}
                                    </p>
                                )}
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={reply.processing}>
                                        <Send />
                                        Kirim Balasan
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="h-fit">
                        <CardHeader>
                            <CardTitle className="text-base">Detail</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4 text-sm">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Status</span>
                                <Badge
                                    variant="secondary"
                                    className={STATUS_STYLES[ticket.status]}
                                >
                                    {label(statuses, ticket.status)}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Prioritas</span>
                                <Badge
                                    variant="secondary"
                                    className={PRIORITY_STYLES[ticket.priority]}
                                >
                                    {label(priorities, ticket.priority)}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">Karyawan</span>
                                <span>{ticket.employee_name ?? '-'}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">SLA</span>
                                <span className="tabular-nums">
                                    {ticket.sla_due_at ?? '-'}
                                </span>
                            </div>

                            <div className="grid gap-2 border-t pt-4">
                                <Label htmlFor="meta-status">Ubah Status</Label>
                                <Select
                                    value={meta.data.status}
                                    onValueChange={(value) => saveMeta({ status: value })}
                                >
                                    <SelectTrigger id="meta-status" className="w-full">
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
                                <Label htmlFor="meta-priority">Ubah Prioritas</Label>
                                <Select
                                    value={meta.data.priority}
                                    onValueChange={(value) => saveMeta({ priority: value })}
                                >
                                    <SelectTrigger id="meta-priority" className="w-full">
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
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
