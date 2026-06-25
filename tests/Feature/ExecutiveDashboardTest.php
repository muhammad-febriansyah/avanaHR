<?php

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Reimbursement;
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
});

it('renders the executive dashboard with cross-module kpis', function () {
    $this->actingAs($this->admin)
        ->get(route('analytics.executive'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('analytics/executive')
            ->has('kpis.headcount')
            ->has('kpis.turnover_rate')
            ->has('kpis.pending_total')
            ->has('kpis.payroll_net')
            ->has('pending', 4)
            ->has('byDepartment'),
        );
});

it('aggregates pending approvals across modules', function () {
    $employee = Employee::firstOrFail();
    LeaveRequest::factory()->count(2)->create(['employee_id' => $employee->id]);
    Reimbursement::factory()->create(['employee_id' => $employee->id, 'amount' => 250000]);

    $this->actingAs($this->admin)
        ->get(route('analytics.executive'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('kpis.pending_total', fn ($v) => $v >= 3)
            ->where('reimbursementPending', fn ($v) => $v >= 250000),
        );
});

it('shows the latest payroll net total', function () {
    PayrollRun::factory()->create([
        'gross_total' => 60_000_000,
        'net_total' => 50_000_000,
        'tax_total' => 5_000_000,
        'bpjs_total' => 5_000_000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('analytics.executive'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('kpis.payroll_net', 50_000_000)
            ->where('payroll.gross', 60_000_000),
        );
});

it('forbids users without report.view', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('report.view'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->get(route('analytics.executive'))
        ->assertForbidden();
});
