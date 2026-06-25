<?php

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
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

function complianceRun(int $year): PayrollRun
{
    $period = PayrollPeriod::factory()->create(['year' => $year, 'month' => 3]);
    $run = PayrollRun::factory()->create(['period_id' => $period->id]);

    Payslip::factory()->count(3)->create([
        'run_id' => $run->id,
        'tax' => 200_000,
        'bpjs_employee' => 100_000,
        'bpjs_company' => 150_000,
        'gross' => 10_000_000,
    ]);

    return $run;
}

it('renders the compliance report with summary cards', function () {
    complianceRun(2026);

    $this->actingAs($this->admin)
        ->get(route('reports.compliance', ['year' => 2026]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/compliance')
            ->where('year', 2026)
            ->has('runs.data', 1)
            ->has('summary.pph21')
            ->has('years'),
        );
});

it('aggregates pph21 and bpjs across payslips', function () {
    complianceRun(2026);

    $this->actingAs($this->admin)
        ->get(route('reports.compliance', ['year' => 2026]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.pph21', 600_000)
            ->where('summary.bpjs', 750_000)
            ->where('runs.data.0.employees', 3)
            ->where('runs.data.0.bpjs_total', 750_000),
        );
});

it('filters runs by year', function () {
    complianceRun(2026);
    complianceRun(2025);

    $this->actingAs($this->admin)
        ->get(route('reports.compliance', ['year' => 2025]))
        ->assertInertia(fn (Assert $page) => $page->has('runs.data', 1));
});

it('forbids users without report.view', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('report.view'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->get(route('reports.compliance'))
        ->assertForbidden();
});
