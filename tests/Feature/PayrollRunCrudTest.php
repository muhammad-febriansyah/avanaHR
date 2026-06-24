<?php

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
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
    $this->period = PayrollPeriod::factory()->create(['code' => 'PR-2026-07']);
});

it('renders the payroll runs index', function () {
    $this->actingAs($this->admin)
        ->get(route('payroll-runs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payroll-runs/index')
            ->has('runs.data')
            ->has('types')
            ->has('statuses')
            ->has('options.periods'),
        );
});

it('creates a run with a generated run number scoped to the period', function () {
    $this->actingAs($this->admin)
        ->post(route('payroll-runs.store'), ['period_id' => $this->period->id, 'type' => 'regular'])
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.store'), ['period_id' => $this->period->id, 'type' => 'regular'])
        ->assertRedirect();

    $runs = PayrollRun::where('period_id', $this->period->id)->orderBy('id')->pluck('run_no')->all();

    expect($runs)->toBe(['RUN-PR-2026-07-01', 'RUN-PR-2026-07-02']);
});

it('rejects an invalid type', function () {
    $this->actingAs($this->admin)
        ->post(route('payroll-runs.store'), ['period_id' => $this->period->id, 'type' => 'special'])
        ->assertSessionHasErrors('type');
});

it('updates a run status', function () {
    $run = PayrollRun::factory()->create(['period_id' => $this->period->id]);

    $this->actingAs($this->admin)
        ->put(route('payroll-runs.update', $run), ['type' => 'regular', 'status' => 'approved'])
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('approved');
});

it('blocks deleting a non-draft run', function () {
    $run = PayrollRun::factory()->approved()->create(['period_id' => $this->period->id]);

    $this->actingAs($this->admin)
        ->delete(route('payroll-runs.destroy', $run))
        ->assertRedirect();

    $this->assertDatabaseHas('payroll_runs', ['id' => $run->id]);
});

it('deletes a draft run', function () {
    $run = PayrollRun::factory()->create(['period_id' => $this->period->id]);

    $this->actingAs($this->admin)
        ->delete(route('payroll-runs.destroy', $run))
        ->assertRedirect();

    $this->assertDatabaseMissing('payroll_runs', ['id' => $run->id]);
});

it('forbids users without payroll.run from creating runs', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('payroll.run'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('payroll-runs.store'), ['period_id' => $this->period->id, 'type' => 'regular'])
        ->assertForbidden();
});
