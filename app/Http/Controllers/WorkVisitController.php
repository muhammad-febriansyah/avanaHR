<?php

namespace App\Http\Controllers;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Requests\WorkVisit\StoreWorkVisitReportRequest;
use App\Http\Requests\WorkVisit\StoreWorkVisitRequest;
use App\Http\Requests\WorkVisit\UpdateWorkVisitRequest;
use App\Models\Employee;
use App\Models\WorkVisit;
use App\Models\WorkVisitReport;
use App\Support\CurrentTenant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WorkVisitController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WorkVisit::class);

        $filters = $request->only('search', 'status');

        $workVisits = WorkVisit::query()
            ->with('employee:id,first_name,last_name,employee_no')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (WorkVisit $workVisit): array => [
                'id' => $workVisit->id,
                'employee_name' => $workVisit->employee?->fullName(),
                'employee_no' => $workVisit->employee?->employee_no,
                'destination' => $workVisit->destination,
                'start_date' => $workVisit->start_date?->format('Y-m-d'),
                'end_date' => $workVisit->end_date?->format('Y-m-d'),
                'status' => $workVisit->status->value,
                'status_label' => $this->statusLabel($workVisit->status),
            ]);

        return Inertia::render('work-visits/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Kunjungan Kerja', 'href' => route('work-visits.index')],
            ],
            'workVisits' => $workVisits,
            'filters' => (object) $filters,
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', WorkVisit::class);

        return Inertia::render('work-visits/create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Kunjungan Kerja', 'href' => route('work-visits.index')],
                ['title' => 'Ajukan', 'href' => route('work-visits.create')],
            ],
            'employees' => $this->employeeOptions(),
            'transportModes' => $this->transportModeOptions(),
        ]);
    }

    public function store(StoreWorkVisitRequest $request): RedirectResponse
    {
        $workVisit = WorkVisit::create([
            ...$request->validated(),
            'status' => RequestStatus::Pending,
        ]);

        // Route through the approval engine: auto-approves when no flow is
        // configured, otherwise opens a request that approvers act on via the
        // approval inbox.
        $approval = $this->engine->submit($workVisit, $request->user());

        $message = $approval === null
            ? 'Kunjungan kerja dibuat dan disetujui otomatis (belum ada alur persetujuan).'
            : 'Kunjungan kerja diajukan dan menunggu persetujuan.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return redirect()->route('work-visits.show', $workVisit);
    }

    public function show(WorkVisit $workVisit): Response
    {
        $this->authorize('view', $workVisit);

        $workVisit->loadMissing('employee', 'decidedBy', 'reports');

        return Inertia::render('work-visits/show', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Kunjungan Kerja', 'href' => route('work-visits.index')],
                ['title' => 'Detail', 'href' => route('work-visits.show', $workVisit)],
            ],
            'workVisit' => [
                'id' => $workVisit->id,
                'employee_name' => $workVisit->employee?->fullName(),
                'employee_no' => $workVisit->employee?->employee_no,
                'destination' => $workVisit->destination,
                'purpose' => $workVisit->purpose,
                'start_date' => $workVisit->start_date?->format('Y-m-d'),
                'end_date' => $workVisit->end_date?->format('Y-m-d'),
                'transport_mode' => $workVisit->transport_mode,
                'estimated_cost' => $workVisit->estimated_cost !== null ? (int) $workVisit->estimated_cost : null,
                'status' => $workVisit->status->value,
                'status_label' => $this->statusLabel($workVisit->status),
                'notes' => $workVisit->notes,
                'decided_by' => $workVisit->decidedBy?->name,
                'decided_at' => $workVisit->decided_at?->format('Y-m-d H:i'),
                'decision_note' => $workVisit->decision_note,
                'can_decide' => $workVisit->isPending(),
                'can_edit' => $workVisit->isPending(),
                'reports' => $workVisit->reports->map(fn (WorkVisitReport $report): array => [
                    'id' => $report->id,
                    'visited_at' => $report->visited_at?->format('Y-m-d'),
                    'location' => $report->location,
                    'notes' => $report->notes,
                    'attachment_path' => $report->attachment_path,
                    'attachment_url' => $this->attachmentUrl($report->attachment_path),
                ])->all(),
            ],
        ]);
    }

    public function edit(WorkVisit $workVisit): Response|RedirectResponse
    {
        $this->authorize('update', $workVisit);

        if (! $workVisit->isPending()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya pengajuan pending yang bisa diedit.']);

            return back();
        }

        return Inertia::render('work-visits/edit', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Kunjungan Kerja', 'href' => route('work-visits.index')],
                ['title' => 'Edit', 'href' => route('work-visits.edit', $workVisit)],
            ],
            'workVisit' => [
                'id' => $workVisit->id,
                'employee_id' => $workVisit->employee_id,
                'destination' => $workVisit->destination,
                'purpose' => $workVisit->purpose,
                'start_date' => $workVisit->start_date?->format('Y-m-d'),
                'end_date' => $workVisit->end_date?->format('Y-m-d'),
                'transport_mode' => $workVisit->transport_mode,
                'estimated_cost' => $workVisit->estimated_cost !== null ? (int) $workVisit->estimated_cost : null,
                'notes' => $workVisit->notes,
            ],
            'employees' => $this->employeeOptions(),
            'transportModes' => $this->transportModeOptions(),
        ]);
    }

    public function update(UpdateWorkVisitRequest $request, WorkVisit $workVisit): RedirectResponse
    {
        if (! $workVisit->isPending()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya pengajuan pending yang bisa diedit.']);

            return back();
        }

        $workVisit->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kunjungan kerja diperbarui.']);

        return redirect()->route('work-visits.index');
    }

    public function storeReport(StoreWorkVisitReportRequest $request, WorkVisit $workVisit): RedirectResponse
    {
        $this->authorize('update', $workVisit);

        $data = $request->safe()->except('attachment');

        if ($request->hasFile('attachment')) {
            // Per-tenant prefix keeps uploaded visit attachments isolated on disk.
            $tenantId = app(CurrentTenant::class)->id();
            $data['attachment_path'] = $request->file('attachment')->store('visits/'.$tenantId, 'public');
        }

        $workVisit->reports()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Laporan kunjungan ditambahkan.']);

        return back();
    }

    /**
     * Resolve a downloadable URL for a stored attachment path. Plain links
     * (e.g. https://… entered manually) are returned as-is.
     */
    private function attachmentUrl(?string $attachmentPath): ?string
    {
        if ($attachmentPath === null || $attachmentPath === '') {
            return null;
        }

        if (Str::startsWith($attachmentPath, ['http://', 'https://'])) {
            return $attachmentPath;
        }

        return Storage::url($attachmentPath);
    }

    public function destroyReport(WorkVisit $workVisit, WorkVisitReport $report): RedirectResponse
    {
        $this->authorize('update', $workVisit);

        $report->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Laporan dihapus.']);

        return back();
    }

    public function destroy(WorkVisit $workVisit): RedirectResponse
    {
        $this->authorize('delete', $workVisit);

        if (! $workVisit->isPending()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya pengajuan pending yang bisa dihapus.']);

            return back();
        }

        $workVisit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kunjungan kerja berhasil dihapus.']);

        return redirect()->route('work-visits.index');
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

    private function statusLabel(RequestStatus $status): string
    {
        return match ($status) {
            RequestStatus::Pending => 'Menunggu',
            RequestStatus::Approved => 'Disetujui',
            RequestStatus::Rejected => 'Ditolak',
            RequestStatus::Revision => 'Revisi',
            RequestStatus::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (RequestStatus $status): array => [
                'value' => $status->value,
                'label' => $this->statusLabel($status),
            ],
            RequestStatus::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function transportModeOptions(): array
    {
        return [
            ['value' => 'mobil', 'label' => 'Mobil'],
            ['value' => 'pesawat', 'label' => 'Pesawat'],
            ['value' => 'kereta', 'label' => 'Kereta'],
            ['value' => 'bus', 'label' => 'Bus'],
            ['value' => 'kapal', 'label' => 'Kapal'],
            ['value' => 'lainnya', 'label' => 'Lainnya'],
        ];
    }
}
