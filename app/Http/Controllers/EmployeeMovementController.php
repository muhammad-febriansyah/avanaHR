<?php

namespace App\Http\Controllers;

use App\Actions\Employee\ApplyMovementAction;
use App\Enums\EmployeeMovementStatus;
use App\Enums\EmployeeMovementType;
use App\Http\Requests\EmployeeMovement\StoreEmployeeMovementRequest;
use App\Http\Requests\EmployeeMovement\UpdateClearanceItemRequest;
use App\Models\ClearanceItem;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeMovement;
use App\Models\JobGrade;
use App\Models\Position;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeMovementController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmployeeMovement::class);

        $filters = $request->only('search', 'type', 'status');

        $movements = EmployeeMovement::query()
            ->with('employee:id,first_name,last_name,employee_no')
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmployeeMovement $movement): array => [
                'id' => $movement->id,
                'employee_name' => $movement->employee?->fullName(),
                'employee_no' => $movement->employee?->employee_no,
                'type' => $movement->type->value,
                'type_label' => $movement->type->label(),
                'effective_date' => $movement->effective_date?->format('Y-m-d'),
                'status' => $movement->status->value,
                'status_label' => $movement->status->label(),
                'applied_at' => $movement->applied_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('movements/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Mutasi & Movement', 'href' => route('movements.index')],
            ],
            'movements' => $movements,
            'filters' => (object) $filters,
            'types' => EmployeeMovementType::options(),
            'statuses' => EmployeeMovementStatus::options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EmployeeMovement::class);

        $employees = Employee::orderBy('first_name')->get()
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'label' => $employee->fullName().' ('.$employee->employee_no.')',
            ]);

        return Inertia::render('movements/create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Mutasi & Movement', 'href' => route('movements.index')],
                ['title' => 'Buat Movement', 'href' => route('movements.create')],
            ],
            'employees' => $employees,
            'types' => EmployeeMovementType::options(),
            'options' => [
                'positions' => Position::orderBy('name')->get()
                    ->map(fn (Position $position): array => ['id' => $position->id, 'label' => $position->name]),
                'departments' => Department::orderBy('name')->get()
                    ->map(fn (Department $department): array => ['id' => $department->id, 'label' => $department->name]),
                'jobGrades' => JobGrade::orderBy('name')->get()
                    ->map(fn (JobGrade $jobGrade): array => ['id' => $jobGrade->id, 'label' => $jobGrade->name.' ('.$jobGrade->code.')']),
                'managers' => $employees,
                'employmentTypes' => [
                    ['value' => 'permanent', 'label' => 'Tetap'],
                    ['value' => 'contract', 'label' => 'Kontrak'],
                    ['value' => 'intern', 'label' => 'Magang'],
                    ['value' => 'outsource', 'label' => 'Outsource'],
                ],
            ],
        ]);
    }

    public function store(StoreEmployeeMovementRequest $request): RedirectResponse
    {
        $this->authorize('create', EmployeeMovement::class);

        $data = $request->validated();
        $type = EmployeeMovementType::from($data['type']);

        $payload = [];
        foreach (['position_id', 'department_id', 'job_grade_id', 'manager_id', 'employment_type'] as $field) {
            if (($data[$field] ?? null) !== null) {
                $payload[$field] = $data[$field];
            }
        }

        $movement = EmployeeMovement::create([
            'employee_id' => $data['employee_id'],
            'type' => $type,
            'effective_date' => $data['effective_date'],
            'status' => EmployeeMovementStatus::Draft,
            'payload_json' => $payload,
            'reason' => $data['reason'] ?? null,
            'requires_clearance' => $type->isExit(),
        ]);

        if ($type->isExit()) {
            foreach (ClearanceItem::defaultChecklist() as $item) {
                ClearanceItem::create([
                    'employee_movement_id' => $movement->id,
                    'category' => $item['category'],
                    'label' => $item['label'],
                    'status' => 'pending',
                ]);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Movement berhasil dibuat.']);

        return redirect()->route('movements.show', $movement);
    }

    public function show(EmployeeMovement $movement): Response
    {
        $this->authorize('view', $movement);

        $movement->loadMissing('employee', 'appliedBy', 'clearanceItems.completedBy');

        return Inertia::render('movements/show', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Mutasi & Movement', 'href' => route('movements.index')],
                ['title' => 'Detail', 'href' => route('movements.show', $movement)],
            ],
            'movement' => $this->presentMovement($movement),
        ]);
    }

    public function apply(EmployeeMovement $movement, ApplyMovementAction $action): RedirectResponse
    {
        $this->authorize('apply', $movement);

        if (! $movement->canApply()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Movement tidak bisa diterapkan.']);

            return back();
        }

        if ($movement->hasPendingClearance()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Clearance belum selesai. Selesaikan semua item sebelum menerapkan.']);

            return back();
        }

        $action->execute($movement, request()->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Movement berhasil diterapkan.']);

        return back();
    }

    public function schedule(EmployeeMovement $movement): RedirectResponse
    {
        $this->authorize('apply', $movement);

        if (! $movement->canSchedule()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya draft yang bisa dijadwalkan.']);

            return back();
        }

        $movement->update(['status' => EmployeeMovementStatus::Scheduled]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Movement dijadwalkan. Akan diterapkan otomatis pada tanggal efektif.']);

        return back();
    }

    public function unschedule(EmployeeMovement $movement): RedirectResponse
    {
        $this->authorize('apply', $movement);

        if (! $movement->canUnschedule()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Movement tidak sedang terjadwal.']);

            return back();
        }

        $movement->update(['status' => EmployeeMovementStatus::Draft]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal dibatalkan, movement kembali draft.']);

        return back();
    }

    public function updateClearance(UpdateClearanceItemRequest $request, ClearanceItem $clearanceItem): RedirectResponse
    {
        $this->authorize('apply', $clearanceItem->movement);

        $data = $request->validated();

        $clearanceItem->update([
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'completed_by' => $request->user()->id,
            'completed_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item clearance diperbarui.']);

        return back();
    }

    public function destroy(EmployeeMovement $movement): RedirectResponse
    {
        $this->authorize('delete', $movement);

        if ($movement->status !== EmployeeMovementStatus::Draft) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya movement draft yang bisa dihapus.']);

            return back();
        }

        $movement->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Movement berhasil dihapus.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMovement(EmployeeMovement $movement): array
    {
        $isPending = in_array($movement->status, [EmployeeMovementStatus::Draft, EmployeeMovementStatus::Scheduled], true);

        if ($isPending) {
            $current = $movement->employee?->currentEmployment()->first();
            $before = $this->employmentSnapshot($current);
            $after = $this->projectedSnapshot($current, $movement->payload_json ?? []);
        } else {
            $before = $this->resolveSnapshot($movement->before_json);
            $after = $this->resolveSnapshot($movement->after_json);
        }

        return [
            'id' => $movement->id,
            'employee_name' => $movement->employee?->fullName(),
            'employee_no' => $movement->employee?->employee_no,
            'type' => $movement->type->value,
            'type_label' => $movement->type->label(),
            'effective_date' => $movement->effective_date?->format('Y-m-d'),
            'status' => $movement->status->value,
            'status_label' => $movement->status->label(),
            'reason' => $movement->reason,
            'requires_clearance' => $movement->requires_clearance,
            'applied_at' => $movement->applied_at?->format('Y-m-d H:i'),
            'applied_by' => $movement->appliedBy?->name,
            'can_apply' => $movement->isApplicable(),
            'can_schedule' => $movement->canSchedule(),
            'can_unschedule' => $movement->canUnschedule(),
            'clearance' => $this->presentClearance($movement),
            'before' => $before,
            'after' => $after,
        ];
    }

    /**
     * Build the clearance gate payload for the movement detail page.
     *
     * @return array{
     *     required: bool,
     *     pending_count: int,
     *     items: list<array{
     *         id: int,
     *         category: string,
     *         label: string,
     *         status: string,
     *         status_label: string,
     *         note: string|null,
     *         completed_by: string|null,
     *         completed_at: string|null
     *     }>
     * }|null
     */
    private function presentClearance(EmployeeMovement $movement): ?array
    {
        if (! $movement->requires_clearance) {
            return null;
        }

        $items = $movement->clearanceItems->sortBy('id')->values();

        return [
            'required' => true,
            'pending_count' => $items->where('status', 'pending')->count(),
            'items' => $items->map(fn (ClearanceItem $item): array => [
                'id' => $item->id,
                'category' => $item->category,
                'label' => $item->label,
                'status' => $item->status,
                'status_label' => $item->statusLabel(),
                'note' => $item->note,
                'completed_by' => $item->completedBy?->name,
                'completed_at' => $item->completed_at?->format('Y-m-d H:i'),
            ])->all(),
        ];
    }

    /**
     * Build a resolved snapshot from a current employment record.
     *
     * @return array<string, mixed>|null
     */
    private function employmentSnapshot(?EmployeeEmployment $employment): ?array
    {
        if ($employment === null) {
            return null;
        }

        return $this->resolveSnapshot([
            'position_id' => $employment->position_id,
            'department_id' => $employment->department_id,
            'job_grade_id' => $employment->job_grade_id,
            'manager_id' => $employment->manager_id,
            'employment_type' => $employment->employment_type instanceof \BackedEnum
                ? $employment->employment_type->value
                : $employment->employment_type,
            'status' => $employment->status,
        ]);
    }

    /**
     * Apply a draft payload over the current employment to preview the result.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function projectedSnapshot(?EmployeeEmployment $current, array $payload): array
    {
        $base = [
            'position_id' => $current?->position_id,
            'department_id' => $current?->department_id,
            'job_grade_id' => $current?->job_grade_id,
            'manager_id' => $current?->manager_id,
            'employment_type' => $current?->employment_type instanceof \BackedEnum
                ? $current->employment_type->value
                : $current?->employment_type,
            'status' => $current?->status,
        ];

        foreach (['position_id', 'department_id', 'job_grade_id', 'manager_id', 'employment_type'] as $field) {
            if (($payload[$field] ?? null) !== null) {
                $base[$field] = $payload[$field];
            }
        }

        return $this->resolveSnapshot($base);
    }

    /**
     * Resolve id-based snapshot keys into human-readable names.
     *
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    private function resolveSnapshot(?array $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        $position = ($snapshot['position_id'] ?? null) !== null
            ? Position::find($snapshot['position_id']) : null;
        $department = ($snapshot['department_id'] ?? null) !== null
            ? Department::find($snapshot['department_id']) : null;
        $jobGrade = ($snapshot['job_grade_id'] ?? null) !== null
            ? JobGrade::find($snapshot['job_grade_id']) : null;
        $manager = ($snapshot['manager_id'] ?? null) !== null
            ? Employee::find($snapshot['manager_id']) : null;

        return [
            'position' => $position?->name,
            'department' => $department?->name,
            'job_grade' => $jobGrade?->name,
            'manager' => $manager?->fullName(),
            'employment_type' => $snapshot['employment_type'] ?? null,
            'status' => $snapshot['status'] ?? null,
        ];
    }
}
