<?php

use App\Models\Employee;
use App\Models\EmployeeLifecycleEvent;
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

it('renders the workforce analytics with kpis and breakdowns', function () {
    $this->actingAs($this->admin)
        ->get(route('analytics.workforce'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('analytics/workforce')
            ->has('kpis.total')
            ->has('kpis.active')
            ->has('byStatus')
            ->has('byDepartment')
            ->has('byGender')
            ->has('byType')
            ->has('hireTrend', 6),
        );
});

it('counts hires in the current month', function () {
    Employee::factory()->create(['join_date' => now()->format('Y-m-d')]);

    $this->actingAs($this->admin)
        ->get(route('analytics.workforce'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('kpis.hires_this_month', fn ($v) => $v >= 1),
        );
});

it('counts separations from lifecycle events this month', function () {
    $employee = Employee::firstOrFail();
    EmployeeLifecycleEvent::factory()->type('resign')->create([
        'employee_id' => $employee->id,
        'effective_date' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($this->admin)
        ->get(route('analytics.workforce'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('kpis.separations_this_month', fn ($v) => $v >= 1),
        );
});

it('forbids users without report.view', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('report.view'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->get(route('analytics.workforce'))
        ->assertForbidden();
});
