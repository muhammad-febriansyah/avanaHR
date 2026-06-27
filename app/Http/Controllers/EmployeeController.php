<?php

namespace App\Http\Controllers;

use App\Actions\Employee\CreateEmployeeAction;
use App\Actions\Employee\DeleteEmployeeAction;
use App\Actions\Employee\UpdateEmployeeAction;
use App\Enums\EmployeeStatus;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\AuditLog;
use App\Models\CustomField;
use App\Models\Employee;
use App\Repositories\Employee\EmployeeRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly EmployeeRepository $employees) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $employees = $this->employees->paginateForIndex(
            $request->only('search', 'status', 'sort', 'dir', 'per_page'),
        );

        if (! $request->user()->can('employee.view_sensitive')) {
            $employees->getCollection()->transform(fn (Employee $e): Employee => $e->maskSensitiveAttributes());
        }

        return Inertia::render('employees/index', [
            'employees' => $employees,
            'filters' => (object) $request->only('search', 'status', 'sort', 'dir', 'per_page'),
            'statuses' => $this->statusOptions(),
            'stats' => (object) $this->employees->statusCounts(),
            'breadcrumbs' => $this->baseCrumbs(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Employee::class);

        return Inertia::render('employees/create', [
            'statuses' => $this->statusOptions(),
            'customFields' => $this->customFieldDefinitions(),
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => 'Tambah', 'href' => route('employees.create')]],
        ]);
    }

    public function store(StoreEmployeeRequest $request, CreateEmployeeAction $action): RedirectResponse
    {
        $employee = $action->handle($request->validated());

        $this->writeAudit($request, $employee, 'created', null, [
            'employee_no' => $employee->employee_no,
            'full_name' => $employee->fullName(),
            'status' => $employee->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Karyawan berhasil ditambahkan.']);

        return redirect()->route('employees.index');
    }

    public function show(Employee $employee): Response
    {
        $this->authorize('view', $employee);

        $detail = $this->employees->loadDetail($employee);

        if (! request()->user()->can('employee.view_sensitive')) {
            $detail->maskSensitiveAttributes();
        }

        return Inertia::render('employees/show', [
            'employee' => $detail,
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => $employee->fullName(), 'href' => route('employees.show', $employee)]],
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('update', $employee);

        return Inertia::render('employees/edit', [
            'employee' => $employee,
            'statuses' => $this->statusOptions(),
            'customFields' => $this->customFieldDefinitions(),
            'customFieldValues' => $this->customFieldValuesFor($employee),
            'breadcrumbs' => [
                ...$this->baseCrumbs(),
                ['title' => $employee->fullName(), 'href' => route('employees.show', $employee)],
                ['title' => 'Edit', 'href' => route('employees.edit', $employee)],
            ],
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, UpdateEmployeeAction $action): RedirectResponse
    {
        $result = $action->handle($employee, $request->validated(), $request->user());

        $message = $result['pending']
            ? 'Perubahan data karyawan diajukan dan menunggu persetujuan.'
            : 'Data karyawan berhasil diperbarui.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return redirect()->route('employees.index');
    }

    public function destroy(Request $request, Employee $employee, DeleteEmployeeAction $action): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $snapshot = [
            'employee_no' => $employee->employee_no,
            'full_name' => $employee->fullName(),
            'status' => $employee->status->value,
        ];

        $action->handle($employee);

        $this->writeAudit($request, $employee, 'deleted', $snapshot, null);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Karyawan berhasil dihapus.']);

        return redirect()->route('employees.index');
    }

    /**
     * Record an audit-log entry for an employee lifecycle action.
     *
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function writeAudit(Request $request, Employee $employee, string $event, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'tenant_id' => $employee->tenant_id,
            'user_id' => $request->user()->id,
            'auditable_type' => Employee::class,
            'auditable_id' => $employee->id,
            'event' => $event,
            'old_values' => $old,
            'new_values' => $new,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }

    /**
     * Base breadcrumb trail shared by all employee pages.
     *
     * @return list<array{title: string, href: string}>
     */
    private function baseCrumbs(): array
    {
        return [
            ['title' => 'Dashboard', 'href' => route('dashboard')],
            ['title' => 'Karyawan', 'href' => route('employees.index')],
        ];
    }

    /**
     * Tenant-defined custom field definitions for the employee entity.
     *
     * @return list<array{key: string, label: string, type: string, options: array<int, string>|null, is_required: bool}>
     */
    private function customFieldDefinitions(): array
    {
        return CustomField::query()
            ->where('entity_type', 'employee')
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(fn (CustomField $field): array => [
                'key' => $field->key,
                'label' => $field->label,
                'type' => $field->type,
                'options' => $field->options,
                'is_required' => $field->is_required,
            ])
            ->all();
    }

    /**
     * Existing custom field values for an employee, keyed by field key.
     *
     * @return array<string, mixed>
     */
    private function customFieldValuesFor(Employee $employee): array
    {
        return $employee->customFieldValues()
            ->with('customField:id,key')
            ->get()
            ->mapWithKeys(fn ($value): array => [$value->customField->key => $value->value])
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(fn (EmployeeStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ], EmployeeStatus::cases());
    }
}
