<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeSalaryComponent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Effective-dated history for one employee: every employment version (with a
 * diff vs the prior one) and the salary-component history. The "as-of" view is
 * resolved client-side from the effective/end dates.
 */
class EmployeeHistoryController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, Employee $employee): Response
    {
        abort_unless($request->user()?->can('employee.view'), 403);

        $employee->load(['employments' => fn ($q) => $q
            ->with(['company', 'branch', 'department', 'position', 'jobGrade', 'costCenter', 'manager'])
            ->orderByDesc('effective_date')
            ->orderByDesc('id'),
        ]);

        $versions = $employee->employments->values();

        $employments = $versions->map(function (EmployeeEmployment $emp, int $i) use ($versions): array {
            $older = $versions->get($i + 1); // next older version

            return [
                'id' => $emp->id,
                'effective_date' => $emp->effective_date?->format('Y-m-d'),
                'end_date' => $emp->end_date?->format('Y-m-d'),
                'company' => $emp->company?->name,
                'branch' => $emp->branch?->name,
                'department' => $emp->department?->name,
                'position' => $emp->position?->name,
                'job_grade' => $emp->jobGrade?->name ?? $emp->jobGrade?->code,
                'cost_center' => $emp->costCenter?->name,
                'manager' => $emp->manager?->fullName(),
                'employment_type' => $emp->employment_type,
                'status' => $emp->status,
                'changes' => $older ? $this->diff($emp, $older) : ['Awal'],
            ];
        });

        $salary = $employee->salaryComponents()
            ->with('component:id,code,name,type,calc_type')
            ->orderByDesc('effective_date')
            ->orderBy('component_id')
            ->get()
            ->map(fn (EmployeeSalaryComponent $sc): array => [
                'id' => $sc->id,
                'component_name' => $sc->component?->name,
                'type' => $sc->component?->type,
                'calc_type' => $sc->component?->calc_type,
                'amount' => (int) $sc->amount,
                'rate' => (float) $sc->rate,
                'effective_date' => $sc->effective_date?->format('Y-m-d'),
            ]);

        return Inertia::render('employees/history', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Karyawan', 'href' => route('employees.index')],
                ['title' => $employee->fullName(), 'href' => route('employees.show', $employee)],
                ['title' => 'Riwayat', 'href' => route('employees.history', $employee)],
            ],
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->fullName(),
                'employee_no' => $employee->employee_no,
            ],
            'employments' => $employments,
            'salary' => $salary,
        ]);
    }

    /**
     * Human labels of fields that differ between a version and the older one.
     *
     * @return list<string>
     */
    private function diff(EmployeeEmployment $new, EmployeeEmployment $old): array
    {
        $fields = [
            'position_id' => 'Jabatan',
            'department_id' => 'Departemen',
            'job_grade_id' => 'Grade',
            'manager_id' => 'Atasan',
            'cost_center_id' => 'Cost Center',
            'branch_id' => 'Cabang',
            'employment_type' => 'Tipe',
            'status' => 'Status',
        ];

        $changes = [];
        foreach ($fields as $column => $label) {
            if ($new->{$column} !== $old->{$column}) {
                $changes[] = $label;
            }
        }

        return $changes;
    }
}
