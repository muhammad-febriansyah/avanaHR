<?php

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->employee = Employee::firstOrFail();
});

it('renders the payslips index', function () {
    Payslip::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->get(route('payslips.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payslips/index')
            ->has('payslips.data', 1)
            ->has('options.runs'),
        );
});

it('shows a payslip with its lines split by type', function () {
    $payslip = Payslip::factory()->create(['employee_id' => $this->employee->id]);
    PayslipLine::factory()->create(['payslip_id' => $payslip->id, 'type' => 'earning']);
    PayslipLine::factory()->create(['payslip_id' => $payslip->id, 'type' => 'deduction']);

    $this->actingAs($this->admin)
        ->get(route('payslips.show', $payslip))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payslips/show')
            ->where('payslip.id', $payslip->id)
            ->has('payslip.lines', 2),
        );
});

it('filters payslips by employee search', function () {
    $other = Employee::where('id', '!=', $this->employee->id)->firstOrFail();
    Payslip::factory()->create(['employee_id' => $this->employee->id]);
    Payslip::factory()->create(['employee_id' => $other->id]);

    $this->actingAs($this->admin)
        ->get(route('payslips.index', ['search' => $this->employee->first_name]))
        ->assertInertia(fn (Assert $page) => $page->has('payslips.data', 1));
});

it('forbids users without payroll.view', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('payroll.view'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->get(route('payslips.index'))
        ->assertForbidden();
});
