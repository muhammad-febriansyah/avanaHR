<?php

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollComponent;
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

function componentPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'BASIC',
        'name' => 'Gaji Pokok',
        'type' => 'earning',
        'calc_type' => 'fixed',
        'is_taxable' => true,
        'is_bpjs_base' => true,
    ], $overrides);
}

it('renders the payroll components index', function () {
    $this->actingAs($this->admin)
        ->get(route('payroll-components.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payroll-components/index')
            ->has('components'),
        );
});

it('creates a component', function () {
    $this->actingAs($this->admin)
        ->post(route('payroll-components.store'), componentPayload())
        ->assertRedirect();

    $this->assertDatabaseHas('payroll_components', [
        'tenant_id' => $this->tenant->id,
        'code' => 'BASIC',
        'type' => 'earning',
        'is_bpjs_base' => true,
    ]);
});

it('rejects a duplicate code within the tenant', function () {
    PayrollComponent::factory()->create(['code' => 'BASIC']);

    $this->actingAs($this->admin)
        ->post(route('payroll-components.store'), componentPayload(['code' => 'BASIC']))
        ->assertSessionHasErrors('code');
});

it('rejects an invalid type', function () {
    $this->actingAs($this->admin)
        ->post(route('payroll-components.store'), componentPayload(['type' => 'bonus']))
        ->assertSessionHasErrors('type');
});

it('updates a component', function () {
    $component = PayrollComponent::factory()->create(['code' => 'TRP', 'name' => 'Lama']);

    $this->actingAs($this->admin)
        ->put(route('payroll-components.update', $component), componentPayload(['code' => 'TRP', 'name' => 'Tunjangan Transport']))
        ->assertRedirect();

    expect($component->fresh()->name)->toBe('Tunjangan Transport');
});

it('blocks deleting a component used in a salary structure', function () {
    $component = PayrollComponent::factory()->create();
    EmployeeSalaryComponent::create([
        'employee_id' => Employee::firstOrFail()->id,
        'component_id' => $component->id,
        'amount' => 1000000,
        'effective_date' => '2026-01-01',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('payroll-components.destroy', $component))
        ->assertRedirect();

    $this->assertNotSoftDeleted($component);
});

it('deletes an unused component', function () {
    $component = PayrollComponent::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('payroll-components.destroy', $component))
        ->assertRedirect();

    $this->assertSoftDeleted($component);
});

it('forbids users without payroll.run from creating components', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('payroll.run'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('payroll-components.store'), componentPayload())
        ->assertForbidden();
});
