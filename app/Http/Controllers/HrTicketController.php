<?php

namespace App\Http\Controllers;

use App\Http\Requests\HrTicket\ReplyHrTicketRequest;
use App\Http\Requests\HrTicket\StoreHrTicketRequest;
use App\Http\Requests\HrTicket\UpdateHrTicketRequest;
use App\Models\Employee;
use App\Models\HrTicket;
use App\Models\HrTicketMessage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HrTicketController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', HrTicket::class);

        $filters = $request->only('search', 'status', 'priority');

        $tickets = HrTicket::query()
            ->with('employee:id,first_name,last_name,employee_no')
            ->withCount('messages')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('subject', 'like', "%{$search}%")
                ->orWhere('ticket_no', 'like', "%{$search}%")))
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'resolved' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (HrTicket $ticket): array => $this->row($ticket));

        return Inertia::render('hr-tickets/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Tiket HR', 'href' => route('hr-tickets.index')],
            ],
            'tickets' => $tickets,
            'filters' => (object) $filters,
            'statuses' => $this->statusOptions(),
            'priorities' => $this->priorityOptions(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', HrTicket::class);

        return Inertia::render('hr-tickets/create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Tiket HR', 'href' => route('hr-tickets.index')],
                ['title' => 'Buat', 'href' => route('hr-tickets.create')],
            ],
            'categories' => $this->categoryOptions(),
            'priorities' => $this->priorityOptions(),
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(StoreHrTicketRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $ticket = HrTicket::create([
            'ticket_no' => $this->nextTicketNo(),
            'employee_id' => $data['employee_id'] ?? null,
            'category' => $data['category'],
            'subject' => $data['subject'],
            'status' => 'open',
            'priority' => $data['priority'],
            'sla_due_at' => $this->slaFor($data['priority']),
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tiket {$ticket->ticket_no} berhasil dibuat."]);

        return to_route('hr-tickets.show', $ticket);
    }

    public function show(HrTicket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'employee:id,first_name,last_name,employee_no',
            'assignedTo:id,name',
            'messages' => fn ($query) => $query->with('user:id,name')->orderBy('id'),
        ]);

        return Inertia::render('hr-tickets/show', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Tiket HR', 'href' => route('hr-tickets.index')],
                ['title' => $ticket->ticket_no, 'href' => route('hr-tickets.show', $ticket)],
            ],
            'ticket' => [
                ...$this->row($ticket),
                'assigned_to' => $ticket->assignedTo?->name,
                'messages' => $ticket->messages->map(fn (HrTicketMessage $message): array => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'author' => $message->user?->name ?? 'Sistem',
                    'created_at' => $message->created_at?->format('Y-m-d H:i'),
                ]),
            ],
            'statuses' => $this->statusOptions(),
            'priorities' => $this->priorityOptions(),
        ]);
    }

    public function reply(ReplyHrTicketRequest $request, HrTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        // A reply on a closed/resolved ticket reopens the conversation.
        if (in_array($ticket->status, ['resolved', 'closed'], true)) {
            $ticket->update(['status' => 'in_progress', 'resolved_at' => null]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Balasan terkirim.']);

        return back();
    }

    public function update(UpdateHrTicketRequest $request, HrTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $status = $request->validated('status');

        $ticket->update([
            'status' => $status,
            'priority' => $request->validated('priority'),
            'resolved_at' => in_array($status, ['resolved', 'closed'], true) ? ($ticket->resolved_at ?? Carbon::now()) : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tiket diperbarui.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(HrTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'employee_name' => $ticket->employee?->fullName(),
            'employee_no' => $ticket->employee?->employee_no,
            'messages_count' => $ticket->messages_count ?? $ticket->messages()->count(),
            'sla_due_at' => $ticket->sla_due_at?->format('Y-m-d H:i'),
            'created_at' => $ticket->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function nextTicketNo(): string
    {
        $sequence = HrTicket::query()->count() + 1;

        return 'TKT-'.Carbon::now()->format('Ym').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function slaFor(string $priority): Carbon
    {
        $hours = match ($priority) {
            'urgent' => 4,
            'high' => 8,
            'medium' => 24,
            default => 48,
        };

        return Carbon::now()->addHours($hours);
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function employeeOptions(): Collection
    {
        return Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'label' => $employee->fullName().' ('.$employee->employee_no.')',
            ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => 'open', 'label' => 'Terbuka'],
            ['value' => 'in_progress', 'label' => 'Diproses'],
            ['value' => 'resolved', 'label' => 'Selesai'],
            ['value' => 'closed', 'label' => 'Ditutup'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function priorityOptions(): array
    {
        return [
            ['value' => 'low', 'label' => 'Rendah'],
            ['value' => 'medium', 'label' => 'Sedang'],
            ['value' => 'high', 'label' => 'Tinggi'],
            ['value' => 'urgent', 'label' => 'Mendesak'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categoryOptions(): array
    {
        return [
            ['value' => 'leave', 'label' => 'Cuti'],
            ['value' => 'payroll', 'label' => 'Payroll'],
            ['value' => 'attendance', 'label' => 'Absensi'],
            ['value' => 'data', 'label' => 'Data Karyawan'],
            ['value' => 'general', 'label' => 'Umum'],
        ];
    }
}
