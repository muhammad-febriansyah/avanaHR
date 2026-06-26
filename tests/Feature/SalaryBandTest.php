<?php

use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeSalaryComponent;
use App\Models\JobGrade;
use App\Models\PayrollComponent;
use App\Models\SalaryStructure;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
});

function employeeWithFixedSalary(int $basic): Employee
{
    $tid = test()->tenant->id;
    $grade = JobGrade::factory()->create(['tenant_id' => $tid]);
    SalaryStructure::factory()->create([
        'tenant_id' => $tid, 'job_grade_id' => $grade->id,
        'band_min' => 8_000_000, 'band_max' => 20_000_000,
    ]);

    $employee = Employee::factory()->create(['tenant_id' => $tid, 'status' => 'active']);
    EmployeeEmployment::factory()->create([
        'tenant_id' => $tid, 'employee_id' => $employee->id, 'job_grade_id' => $grade->id,
        'effective_date' => now()->subYear(), 'end_date' => null,
    ]);

    $component = PayrollComponent::create([
        'tenant_id' => $tid, 'code' => 'GAPOK-'.$employee->id, 'name' => 'Gaji Pokok',
        'type' => 'earning', 'calc_type' => 'fixed', 'formula' => null,
        'is_taxable' => true, 'is_bpjs_base' => true,
    ]);
    EmployeeSalaryComponent::create([
        'tenant_id' => $tid, 'employee_id' => $employee->id, 'component_id' => $component->id,
        'effective_date' => '2020-01-01', 'amount' => $basic, 'rate' => 0,
    ]);

    return $employee;
}

it('flags salary within the grade band', function () {
    $employee = employeeWithFixedSalary(12_000_000); // inside 8jt–20jt

    $this->actingAs($this->admin)
        ->get(route('employees.salary.index', $employee))
        ->assertInertia(fn ($page) => $page
            ->where('salaryBand.total_fixed', 12_000_000)
            ->where('salaryBand.band_min', 8_000_000)
            ->where('salaryBand.within', true)
        );
});

it('flags salary outside the grade band', function () {
    $employee = employeeWithFixedSalary(25_000_000); // above 20jt

    $this->actingAs($this->admin)
        ->get(route('employees.salary.index', $employee))
        ->assertInertia(fn ($page) => $page
            ->where('salaryBand.total_fixed', 25_000_000)
            ->where('salaryBand.within', false)
        );
});

it('returns no band when the grade has no structure', function () {
    $tid = $this->tenant->id;
    $grade = JobGrade::factory()->create(['tenant_id' => $tid]); // no SalaryStructure
    $employee = Employee::factory()->create(['tenant_id' => $tid]);
    EmployeeEmployment::factory()->create([
        'tenant_id' => $tid, 'employee_id' => $employee->id, 'job_grade_id' => $grade->id,
        'effective_date' => now()->subYear(), 'end_date' => null,
    ]);

    $this->actingAs($this->admin)
        ->get(route('employees.salary.index', $employee))
        ->assertInertia(fn ($page) => $page->where('salaryBand', null));
});
