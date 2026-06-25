<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeSalaryComponent\StoreEmployeeSalaryComponentRequest;
use App\Http\Requests\EmployeeSalaryComponent\UpdateEmployeeSalaryComponentRequest;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollComponent;
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
                'amount' => (int) $salaryComponent->amount,
                'effective_date' => $salaryComponent->effective_date?->format('Y-m-d'),
            ]);

        $components = PayrollComponent::orderBy('name')
            ->get()
            ->map(fn (PayrollComponent $component): array => [
                'id' => $component->id,
                'code' => $component->code,
                'name' => $component->name,
                'type' => $component->type,
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
        ]);
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
