<?php

use App\Enums\RequestStatus;
use App\Models\Employee;
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
    $this->employee = Employee::firstOrFail();
});

function reimbursementPayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'category' => 'medical',
        'amount' => 500000,
        'settlement' => 'payroll',
    ], $overrides);
}

it('renders the reimbursements index', function () {
    $this->actingAs($this->admin)
        ->get(route('reimbursements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reimbursements/index')
            ->has('reimbursements.data')
            ->has('statuses')
            ->has('categories')
            ->has('settlements')
            ->has('options.employees'),
        );
});

it('creates a reimbursement as pending', function () {
    $this->actingAs($this->admin)
        ->post(route('reimbursements.store'), reimbursementPayload())
        ->assertRedirect();

    $this->assertDatabaseHas('reimbursements', [
        'tenant_id' => $this->tenant->id,
        'employee_id' => $this->employee->id,
        'category' => 'medical',
        'amount' => 500000,
        'status' => RequestStatus::Pending->value,
    ]);
});

it('rejects an invalid category', function () {
    $this->actingAs($this->admin)
        ->post(route('reimbursements.store'), reimbursementPayload(['category' => 'travel']))
        ->assertSessionHasErrors('category');
});

it('rejects a zero amount', function () {
    $this->actingAs($this->admin)
        ->post(route('reimbursements.store'), reimbursementPayload(['amount' => 0]))
        ->assertSessionHasErrors('amount');
});

it('approves a pending reimbursement', function () {
    $reimbursement = Reimbursement::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->patch(route('reimbursements.decide', $reimbursement), ['status' => 'approved'])
        ->assertRedirect();

    expect($reimbursement->fresh()->status)->toBe(RequestStatus::Approved);
});

it('does not re-decide a processed reimbursement', function () {
    $reimbursement = Reimbursement::factory()->approved()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->patch(route('reimbursements.decide', $reimbursement), ['status' => 'rejected'])
        ->assertRedirect();

    expect($reimbursement->fresh()->status)->toBe(RequestStatus::Approved);
});

it('blocks editing a non-pending reimbursement', function () {
    $reimbursement = Reimbursement::factory()->approved()->create([
        'employee_id' => $this->employee->id,
        'amount' => 100000,
    ]);

    $this->actingAs($this->admin)
        ->put(route('reimbursements.update', $reimbursement), reimbursementPayload(['amount' => 999000]))
        ->assertRedirect();

    expect((int) $reimbursement->fresh()->amount)->toBe(100000);
});

it('deletes a reimbursement', function () {
    $reimbursement = Reimbursement::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->delete(route('reimbursements.destroy', $reimbursement))
        ->assertRedirect();

    $this->assertDatabaseMissing('reimbursements', ['id' => $reimbursement->id]);
});

it('forbids users without payroll.run from creating reimbursements', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('payroll.run'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('reimbursements.store'), reimbursementPayload())
        ->assertForbidden();
});
