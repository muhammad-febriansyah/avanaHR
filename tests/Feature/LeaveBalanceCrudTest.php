<?php

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
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
    $this->leaveType = LeaveType::factory()->create();
});

function balancePayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'leave_type_id' => test()->leaveType->id,
        'year' => 2026,
        'entitled' => 12,
        'used' => 2,
        'pending' => 1,
        'expired' => 0,
    ], $overrides);
}

it('renders the leave balances index', function () {
    $this->actingAs($this->admin)
        ->get(route('leave-balances.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leave-balances/index')
            ->has('balances.data')
            ->has('options.employees')
            ->has('options.leaveTypes')
            ->has('options.years'),
        );
});

it('creates a balance and computes available', function () {
    $this->actingAs($this->admin)
        ->post(route('leave-balances.store'), balancePayload())
        ->assertRedirect();

    $this->assertDatabaseHas('leave_balances', [
        'tenant_id' => $this->tenant->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => 2026,
        'entitled' => 12,
        'available' => 9,
    ]);
});

it('rejects a duplicate employee/type/year combination', function () {
    LeaveBalance::create(balancePayload(['available' => 9]));

    $this->actingAs($this->admin)
        ->post(route('leave-balances.store'), balancePayload())
        ->assertSessionHasErrors('employee_id');
});

it('recomputes available on update', function () {
    $balance = LeaveBalance::create(balancePayload(['available' => 9]));

    $this->actingAs($this->admin)
        ->put(route('leave-balances.update', $balance), [
            'entitled' => 15,
            'used' => 5,
            'pending' => 0,
            'expired' => 1,
        ])
        ->assertRedirect();

    expect((float) $balance->fresh()->available)->toBe(9.0);
});

it('filters balances by year', function () {
    LeaveBalance::create(balancePayload(['year' => 2026, 'available' => 9]));
    LeaveBalance::create(balancePayload(['year' => 2025, 'available' => 9]));

    $this->actingAs($this->admin)
        ->get(route('leave-balances.index', ['year' => 2025]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentYear', 2025)
            ->has('balances.data', 1),
        );
});

it('deletes a balance', function () {
    $balance = LeaveBalance::create(balancePayload(['available' => 9]));

    $this->actingAs($this->admin)
        ->delete(route('leave-balances.destroy', $balance))
        ->assertRedirect();

    $this->assertDatabaseMissing('leave_balances', ['id' => $balance->id]);
});

it('forbids users without leave.approve from creating balances', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('leave.approve'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('leave-balances.store'), balancePayload())
        ->assertForbidden();
});
