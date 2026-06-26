<?php

use App\Approvals\ApprovalEngine;
use App\Enums\ApprovalActionType;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Models\ApprovalFlow;
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
    $this->manager = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
        ->firstOrFail();
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

it('creates a pending loan and opens an approval request', function () {
    $this->actingAs($this->admin)
        ->post(route('employee-loans.store'), loanPayload())
        ->assertRedirect();

    $loan = EmployeeLoan::firstOrFail();

    expect((int) $loan->installment)->toBe(500_000); // 6_000_000 / 12
    expect((int) $loan->outstanding)->toBe(6_000_000);
    expect($loan->status)->toBe(RequestStatus::Pending);

    $this->assertDatabaseHas('approval_requests', [
        'approvable_type' => $loan->getMorphClass(),
        'approvable_id' => $loan->id,
        'status' => 'pending',
    ]);
    expect($loan->pendingApprovalRequest())->not->toBeNull();
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

it('approves a pending loan through the approval engine', function () {
    $this->actingAs($this->admin)
        ->post(route('employee-loans.store'), loanPayload())
        ->assertRedirect();

    $loan = EmployeeLoan::firstOrFail();
    $request = $loan->pendingApprovalRequest();

    expect($request)->not->toBeNull();
    expect($request->status)->toBe(ApprovalStatus::Pending);

    app(ApprovalEngine::class)->act($request, $this->manager, ApprovalActionType::Approve);

    expect($request->fresh()->status)->toBe(ApprovalStatus::Approved);
    expect($loan->fresh()->status)->toBe(RequestStatus::Approved);
});

it('auto-approves a loan when no flow is configured', function () {
    ApprovalFlow::where('transaction_type', 'loan')->delete();

    $this->actingAs($this->admin)
        ->post(route('employee-loans.store'), loanPayload())
        ->assertRedirect();

    $loan = EmployeeLoan::firstOrFail();

    expect($loan->status)->toBe(RequestStatus::Approved);
    expect($loan->pendingApprovalRequest())->toBeNull();
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
