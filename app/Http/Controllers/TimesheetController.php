<?php

namespace App\Http\Controllers;

use App\Http\Requests\Timesheet\StoreTimesheetRequest;
use App\Http\Requests\Timesheet\UpdateTimesheetRequest;
use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimesheetController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Timesheet::class);

        $filters = $request->only('search');

        $timesheets = Timesheet::query()
            ->with('employee:id,first_name,last_name,employee_no')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Timesheet $timesheet): array => [
                'id' => $timesheet->id,
                'employee_name' => $timesheet->employee?->fullName(),
                'employee_no' => $timesheet->employee?->employee_no,
                'date' => $timesheet->date?->format('Y-m-d'),
                'hours' => (float) $timesheet->hours,
                'note' => $timesheet->note,
            ]);

        return Inertia::render('timesheets/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Timesheet', 'href' => route('timesheets.index')],
            ],
            'timesheets' => $timesheets,
            'filters' => (object) $filters,
            'options' => [
                'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
                    ->map(fn (Employee $employee): array => [
                        'id' => $employee->id,
                        'label' => $employee->fullName().' ('.$employee->employee_no.')',
                    ]),
            ],
        ]);
    }

    public function store(StoreTimesheetRequest $request): RedirectResponse
    {
        Timesheet::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Timesheet berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdateTimesheetRequest $request, Timesheet $timesheet): RedirectResponse
    {
        $timesheet->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Timesheet berhasil diperbarui.']);

        return back();
    }

    public function destroy(Timesheet $timesheet): RedirectResponse
    {
        $this->authorize('delete', $timesheet);

        $timesheet->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Timesheet berhasil dihapus.']);

        return back();
    }
}
