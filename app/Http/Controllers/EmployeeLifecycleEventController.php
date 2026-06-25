<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeLifecycleEvent\StoreEmployeeLifecycleEventRequest;
use App\Models\Employee;
use App\Models\EmployeeLifecycleEvent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeLifecycleEventController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmployeeLifecycleEvent::class);

        $filters = $request->only('search', 'type');

        $events = EmployeeLifecycleEvent::query()
            ->with('employee:id,first_name,last_name,employee_no')
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
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
            ->through(fn (EmployeeLifecycleEvent $event): array => [
                'id' => $event->id,
                'employee_name' => $event->employee?->fullName(),
                'employee_no' => $event->employee?->employee_no,
                'type' => $event->type,
                'effective_date' => $event->effective_date?->format('Y-m-d'),
                'from_value' => $event->from_json['value'] ?? null,
                'to_value' => $event->to_json['value'] ?? null,
                'reason' => $event->reason,
            ]);

        return Inertia::render('lifecycle/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Lifecycle', 'href' => route('lifecycle.index')],
            ],
            'events' => $events,
            'filters' => (object) $filters,
            'types' => $this->typeOptions(),
            'options' => [
                'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
                    ->map(fn (Employee $employee): array => [
                        'id' => $employee->id,
                        'label' => $employee->fullName().' ('.$employee->employee_no.')',
                    ]),
            ],
        ]);
    }

    public function store(StoreEmployeeLifecycleEventRequest $request): RedirectResponse
    {
        $data = $request->validated();

        EmployeeLifecycleEvent::create([
            'employee_id' => $data['employee_id'],
            'type' => $data['type'],
            'effective_date' => $data['effective_date'],
            'from_json' => isset($data['from_value']) ? ['value' => $data['from_value']] : null,
            'to_json' => isset($data['to_value']) ? ['value' => $data['to_value']] : null,
            'reason' => $data['reason'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Peristiwa lifecycle berhasil dicatat.']);

        return back();
    }

    public function destroy(EmployeeLifecycleEvent $lifecycle): RedirectResponse
    {
        $this->authorize('delete', $lifecycle);

        $lifecycle->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Peristiwa lifecycle berhasil dihapus.']);

        return back();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return [
            ['value' => 'hire', 'label' => 'Bergabung'],
            ['value' => 'promotion', 'label' => 'Promosi'],
            ['value' => 'transfer', 'label' => 'Mutasi'],
            ['value' => 'demotion', 'label' => 'Demosi'],
            ['value' => 'contract_change', 'label' => 'Perubahan Kontrak'],
            ['value' => 'resign', 'label' => 'Resign'],
            ['value' => 'terminate', 'label' => 'Terminasi'],
            ['value' => 'reactivate', 'label' => 'Aktif Kembali'],
        ];
    }
}
