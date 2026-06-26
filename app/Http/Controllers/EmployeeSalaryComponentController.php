<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeSalaryComponent\StoreEmployeeSalaryComponentRequest;
use App\Http\Requests\EmployeeSalaryComponent\UpdateEmployeeSalaryComponentRequest;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollComponent;
use App\Models\SalaryStructure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeSalaryComponentController extends Controller
{
    public function index(Employee $employee): Response
    {
        abort_unless(request()->user()->can('payroll.view'), 403);

        $salaryComponents = $employee->salaryComponents()
            ->with('component')
            ->orderByDesc('effective_date')
            ->get()
            ->map(fn (EmployeeSalaryComponent $salaryComponent): array => [
                'id' => $salaryComponent->id,
                'component_id' => $salaryComponent->component_id,
                'component_code' => $salaryComponent->component?->code,
                'component_name' => $salaryComponent->component?->name,
                'type' => $salaryComponent->component?->type,
                'calc_type' => $salaryComponent->component?->calc_type,
                'amount' => (int) $salaryComponent->amount,
                'rate' => (float) $salaryComponent->rate,
                'effective_date' => $salaryComponent->effective_date?->format('Y-m-d'),
            ]);

        $components = PayrollComponent::orderBy('name')
            ->get()
            ->map(fn (PayrollComponent $component): array => [
                'id' => $component->id,
                'code' => $component->code,
                'name' => $component->name,
                'type' => $component->type,
                'calc_type' => $component->calc_type,
            ]);

        return Inertia::render('employees/salary', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Karyawan', 'href' => route('employees.index')],
                ['title' => $employee->fullName(), 'href' => route('employees.show', $employee)],
                ['title' => 'Komponen Gaji', 'href' => route('employees.salary.index', $employee)],
            ],
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->fullName(),
                'employee_no' => $employee->employee_no,
            ],
            'salaryComponents' => $salaryComponents,
            'components' => $components,
            'salaryBand' => $this->salaryBand($employee),
        ]);
    }

    /**
     * The grade salary band vs the employee's current fixed earnings, so the
     * UI can flag a salary that falls outside the configured range. Null when
     * the employee's grade has no band defined.
     *
     * @return array<string, mixed>|null
     */
    private function salaryBand(Employee $employee): ?array
    {
        $gradeId = $employee->currentEmployment?->job_grade_id;
        if ($gradeId === null) {
            return null;
        }

        $structure = SalaryStructure::query()
            ->with('jobGrade:id,code,name')
            ->where('job_grade_id', $gradeId)
            ->first();

        if ($structure === null) {
            return null;
        }

        // Current fixed earnings: latest effective amount per fixed earning component.
        $totalFixed = (int) $employee->salaryComponents()
            ->with('component')
            ->get()
            ->filter(fn (EmployeeSalaryComponent $sc): bool => $sc->component?->type === 'earning' && $sc->component?->calc_type === 'fixed')
            ->groupBy('component_id')
            ->map(fn ($group) => $group->sortByDesc('effective_date')->first())
            ->sum('amount');

        $min = (int) $structure->band_min;
        $max = (int) $structure->band_max;

        return [
            'grade_code' => $structure->jobGrade?->code,
            'grade_name' => $structure->jobGrade?->name,
            'band_min' => $min,
            'band_max' => $max,
            'total_fixed' => $totalFixed,
            'within' => $totalFixed >= $min && $totalFixed <= $max,
        ];
    }

    public function store(StoreEmployeeSalaryComponentRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        $employee->salaryComponents()->create([
            'component_id' => $data['component_id'],
            'effective_date' => $data['effective_date'],
            'amount' => $data['amount'],
            'rate' => $data['rate'] ?? 0,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komponen gaji ditambahkan.']);

        return back();
    }

    public function update(UpdateEmployeeSalaryComponentRequest $request, EmployeeSalaryComponent $salaryComponent): RedirectResponse
    {
        $salaryComponent->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komponen gaji diperbarui.']);

        return back();
    }

    public function destroy(Request $request, EmployeeSalaryComponent $salaryComponent): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.run'), 403);

        $salaryComponent->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komponen gaji dihapus.']);

        return back();
    }
}
