<?php

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\EmployeeLoan;
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

function loanPayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'principal' => 6_000_000,
        'tenor_months' => 12,
    ], $overrides);
}

it('renders the loans index', function () {
    $this->actingAs($this->admin)
        ->get(route('employee-loans.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employee-loans/index')
            ->has('loans.data')
            ->has('statuses')
            ->has('options.employees'),
        );
});

it('creates a loan deriving installment and outstanding', function () {
    $this->actingAs($this->admin)
        ->post(route('employee-loans.store'), loanPayload())
        ->assertRedirect();

    $loan = EmployeeLoan::firstOrFail();

    expect((int) $loan->installment)->toBe(500_000); // 6_000_000 / 12
    expect((int) $loan->outstanding)->toBe(6_000_000);
    expect($loan->status)->toBe(RequestStatus::Pending);
});

it('rounds the installment up to cover the principal', function () {
    $this->actingAs($this->admin)
        ->post(route('employee-loans.store'), loanPayload(['principal' => 1_000_000, 'tenor_months' => 3]))
        ->assertRedirect();

    expect((int) EmployeeLoan::firstOrFail()->installment)->toBe(333_334); // ceil(1_000_000/3)
});

it('rejects a zero principal', function () {
    $this->actingAs($this->admin)
        ->post(route('employee-loans.store'), loanPayload(['principal' => 0]))
        ->assertSessionHasErrors('principal');
});

it('rejects a zero tenor', function () {
    $this->actingAs($this->admin)
        ->post(route('employee-loans.store'), loanPayload(['tenor_months' => 0]))
        ->assertSessionHasErrors('tenor_months');
});

it('approves a pending loan', function () {
    $loan = EmployeeLoan::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->patch(route('employee-loans.decide', $loan), ['status' => 'approved'])
        ->assertRedirect();

    expect($loan->fresh()->status)->toBe(RequestStatus::Approved);
});

it('blocks editing a non-pending loan', function () {
    $loan = EmployeeLoan::factory()->approved()->create([
        'employee_id' => $this->employee->id,
        'principal' => 3_000_000,
    ]);

    $this->actingAs($this->admin)
        ->put(route('employee-loans.update', $loan), loanPayload(['principal' => 9_000_000]))
        ->assertRedirect();

    expect((int) $loan->fresh()->principal)->toBe(3_000_000);
});

it('deletes a loan', function () {
    $loan = EmployeeLoan::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->delete(route('employee-loans.destroy', $loan))
        ->assertRedirect();

    $this->assertDatabaseMissing('employee_loans', ['id' => $loan->id]);
});

it('forbids users without payroll.run from creating loans', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('payroll.run'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('employee-loans.store'), loanPayload())
        ->assertForbidden();
});
