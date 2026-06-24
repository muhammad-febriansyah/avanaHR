<?php

use App\Enums\PayrollPeriodStatus;
use App\Models\PayrollPeriod;
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

function periodPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'PR-2026-07',
        'month' => 7,
        'year' => 2026,
        'cutoff_date' => '2026-07-25',
        'pay_date' => '2026-07-28',
    ], $overrides);
}

it('renders the payroll periods index', function () {
    $this->actingAs($this->admin)
        ->get(route('payroll-periods.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payroll-periods/index')
            ->has('periods')
            ->has('statuses'),
        );
});

it('creates a period as draft', function () {
    $this->actingAs($this->admin)
        ->post(route('payroll-periods.store'), periodPayload())
        ->assertRedirect();

    $period = PayrollPeriod::firstOrFail();

    expect($period->code)->toBe('PR-2026-07');
    expect((int) $period->month)->toBe(7);
    expect($period->status)->toBe(PayrollPeriodStatus::Draft);
});

it('rejects a duplicate code within the tenant', function () {
    PayrollPeriod::factory()->create(['code' => 'PR-2026-07']);

    $this->actingAs($this->admin)
        ->post(route('payroll-periods.store'), periodPayload(['code' => 'PR-2026-07']))
        ->assertSessionHasErrors('code');
});

it('rejects an out-of-range month', function () {
    $this->actingAs($this->admin)
        ->post(route('payroll-periods.store'), periodPayload(['month' => 13]))
        ->assertSessionHasErrors('month');
});

it('updates a period status', function () {
    $period = PayrollPeriod::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('payroll-periods.update', $period), periodPayload([
            'code' => $period->code,
            'month' => $period->month,
            'year' => $period->year,
            'status' => 'approved',
        ]))
        ->assertRedirect();

    expect($period->fresh()->status)->toBe(PayrollPeriodStatus::Approved);
});

it('blocks deleting a non-draft period', function () {
    $period = PayrollPeriod::factory()->approved()->create();

    $this->actingAs($this->admin)
        ->delete(route('payroll-periods.destroy', $period))
        ->assertRedirect();

    $this->assertDatabaseHas('payroll_periods', ['id' => $period->id]);
});

it('deletes a draft period', function () {
    $period = PayrollPeriod::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('payroll-periods.destroy', $period))
        ->assertRedirect();

    $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
});

it('forbids users without payroll.run from creating periods', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('payroll.run'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('payroll-periods.store'), periodPayload())
        ->assertForbidden();
});
