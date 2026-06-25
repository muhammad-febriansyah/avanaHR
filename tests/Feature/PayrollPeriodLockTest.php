<?php

use App\Enums\PayrollPeriodStatus;
use App\Models\PayrollPeriod;
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

it('closes a draft period', function () {
    $period = PayrollPeriod::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('payroll-periods.close', $period))
        ->assertRedirect();

    $this->assertDatabaseHas('payroll_periods', [
        'id' => $period->id,
        'status' => 'locked',
    ]);
});

it('cannot close a non-draft period', function () {
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Locked]);

    $this->actingAs($this->admin)
        ->post(route('payroll-periods.close', $period))
        ->assertRedirect();

    $this->assertDatabaseHas('payroll_periods', [
        'id' => $period->id,
        'status' => 'locked',
    ]);
});

it('reopens a locked period', function () {
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Locked]);

    $this->actingAs($this->admin)
        ->post(route('payroll-periods.reopen', $period))
        ->assertRedirect();

    $this->assertDatabaseHas('payroll_periods', [
        'id' => $period->id,
        'status' => 'draft',
    ]);
});

it('blocks creating a payroll run in a locked period', function () {
    $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Locked]);

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.store'), [
            'period_id' => $period->id,
            'type' => 'regular',
        ])
        ->assertRedirect();

    $this->assertDatabaseMissing('payroll_runs', ['period_id' => $period->id]);
});

it('forbids close without payroll.approve permission', function () {
    $officer = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $officer->assignRole('payroll-officer');

    expect($officer->can('payroll.run'))->toBeTrue();
    expect($officer->can('payroll.approve'))->toBeFalse();

    $period = PayrollPeriod::factory()->create();

    $this->actingAs($officer)
        ->post(route('payroll-periods.close', $period))
        ->assertForbidden();
});
