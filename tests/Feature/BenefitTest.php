<?php

use App\Approvals\ApprovalEngine;
use App\Approvals\ApprovalException;
use App\Enums\ApprovalActionType;
use App\Enums\RequestStatus;
use App\Models\ApprovalFlow;
use App\Models\BenefitClaim;
use App\Models\BenefitType;
use App\Models\Employee;
use App\Models\EmployeeBenefit;
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
    $this->engine = app(ApprovalEngine::class);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->employee = Employee::query()->first();
    $this->manager = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
        ->firstOrFail();
});

/**
 * Create a benefit type directly (model is guarded against id only).
 *
 * @param  array<string, mixed>  $overrides
 */
function makeBenefitType(array $overrides = []): BenefitType
{
    return BenefitType::create(array_merge([
        'tenant_id' => test()->tenant->id,
        'code' => 'TSTMED',
        'name' => 'Medical',
        'default_quota' => 10000000,
        'description' => null,
        'is_active' => true,
    ], $overrides));
}

/**
 * Create an employee benefit allocation directly.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeEmployeeBenefit(array $overrides = []): EmployeeBenefit
{
    $benefitType = $overrides['benefit_type_id'] ?? makeBenefitType()->id;

    return EmployeeBenefit::create(array_merge([
        'tenant_id' => test()->tenant->id,
        'employee_id' => test()->employee->id,
        'benefit_type_id' => $benefitType,
        'period_year' => 2026,
        'quota' => 5000000,
    ], $overrides));
}

/**
 * Create a benefit claim directly.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeBenefitClaim(EmployeeBenefit $benefit, array $overrides = []): BenefitClaim
{
    return BenefitClaim::create(array_merge([
        'tenant_id' => test()->tenant->id,
        'employee_benefit_id' => $benefit->id,
        'claim_date' => now()->toDateString(),
        'amount' => 1000000,
        'description' => 'Beli obat',
        'status' => 'pending',
    ], $overrides));
}

it('renders the benefit types index', function () {
    $this->actingAs($this->admin)
        ->get(route('benefit-types.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('benefit-types/index')
            ->has('benefitTypes'),
        );
});

it('creates a benefit type', function () {
    $this->actingAs($this->admin)
        ->post(route('benefit-types.store'), [
            'code' => 'TSTMED',
            'name' => 'Medical',
            'default_quota' => 10000000,
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('benefit_types', [
        'code' => 'TSTMED',
        'name' => 'Medical',
        'tenant_id' => $this->tenant->id,
    ]);
});

it('rejects a duplicate benefit type code', function () {
    makeBenefitType(['code' => 'TSTMED']);

    $this->actingAs($this->admin)
        ->post(route('benefit-types.store'), [
            'code' => 'TSTMED',
            'name' => 'Medical Duplicate',
            'default_quota' => 10000000,
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['code']);
});

it('renders the employee benefits index', function () {
    $this->actingAs($this->admin)
        ->get(route('employee-benefits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employee-benefits/index')
            ->has('employeeBenefits'),
        );
});

it('renders the employee benefits create page', function () {
    $this->actingAs($this->admin)
        ->get(route('employee-benefits.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employee-benefits/create')
            ->has('employees')
            ->has('benefitTypes')
            ->has('currentYear'),
        );
});

it('assigns a benefit to an employee', function () {
    $type = makeBenefitType();

    $this->actingAs($this->admin)
        ->post(route('employee-benefits.store'), [
            'employee_id' => $this->employee->id,
            'benefit_type_id' => $type->id,
            'period_year' => 2026,
            'quota' => 5000000,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('employee_benefits', [
        'employee_id' => $this->employee->id,
        'quota' => 5000000,
        'period_year' => 2026,
        'tenant_id' => $this->tenant->id,
    ]);
});

it('rejects a duplicate benefit assignment', function () {
    $type = makeBenefitType();

    makeEmployeeBenefit([
        'benefit_type_id' => $type->id,
        'period_year' => 2026,
    ]);

    $this->actingAs($this->admin)
        ->post(route('employee-benefits.store'), [
            'employee_id' => $this->employee->id,
            'benefit_type_id' => $type->id,
            'period_year' => 2026,
            'quota' => 5000000,
        ])
        ->assertSessionHasErrors();
});

it('adds a claim against an allocation and opens an approval request', function () {
    $benefit = makeEmployeeBenefit(['quota' => 5000000]);

    $this->actingAs($this->admin)
        ->post(route('employee-benefits.claims.store', $benefit), [
            'claim_date' => now()->toDateString(),
            'amount' => 1000000,
            'description' => 'Beli obat',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('benefit_claims', [
        'employee_benefit_id' => $benefit->id,
        'amount' => 1000000,
        'status' => 'pending',
    ]);

    $claim = BenefitClaim::query()->where('employee_benefit_id', $benefit->id)->firstOrFail();

    $this->assertDatabaseHas('approval_requests', [
        'approvable_type' => $claim->getMorphClass(),
        'approvable_id' => $claim->id,
        'status' => 'pending',
    ]);
});

it('approves a claim within the remaining quota via the engine', function () {
    $benefit = makeEmployeeBenefit(['quota' => 5000000]);
    $claim = makeBenefitClaim($benefit, ['amount' => 1000000]);

    $request = $this->engine->submit($claim, $this->admin);
    $this->engine->act($request, $this->manager, ApprovalActionType::Approve);

    expect($claim->fresh()->status)->toBe(RequestStatus::Approved);
    expect($benefit->fresh()->remainingQuota())->toBe(4000000);
});

it('blocks approving a claim exceeding the remaining plafond', function () {
    $benefit = makeEmployeeBenefit(['quota' => 1000000]);
    $claim = makeBenefitClaim($benefit, ['amount' => 2000000]);

    $request = $this->engine->submit($claim, $this->admin);

    expect(fn () => $this->engine->act($request, $this->manager, ApprovalActionType::Approve))
        ->toThrow(ApprovalException::class, 'Klaim melebihi sisa plafon');

    expect($claim->fresh()->status)->toBe(RequestStatus::Pending);
    $this->assertDatabaseHas('benefit_claims', [
        'id' => $claim->id,
        'status' => 'pending',
    ]);
});

it('rejects a claim via the engine', function () {
    $benefit = makeEmployeeBenefit(['quota' => 5000000]);
    $claim = makeBenefitClaim($benefit, ['amount' => 1000000]);

    $request = $this->engine->submit($claim, $this->admin);
    $this->engine->act($request, $this->manager, ApprovalActionType::Reject, 'tidak valid');

    expect($claim->fresh()->status)->toBe(RequestStatus::Rejected);
});

it('auto-approves a within-plafond claim when no flow exists', function () {
    ApprovalFlow::where('transaction_type', 'benefit_claim')->delete();

    $benefit = makeEmployeeBenefit(['quota' => 5000000]);

    $this->actingAs($this->admin)
        ->post(route('employee-benefits.claims.store', $benefit), [
            'claim_date' => now()->toDateString(),
            'amount' => 1000000,
            'description' => 'Beli obat',
        ])
        ->assertRedirect();

    $claim = BenefitClaim::query()->where('employee_benefit_id', $benefit->id)->firstOrFail();

    expect($claim->status)->toBe(RequestStatus::Approved);
    $this->assertDatabaseMissing('approval_requests', [
        'approvable_type' => $claim->getMorphClass(),
        'approvable_id' => $claim->id,
    ]);
});

it('cannot delete a benefit type that is in use', function () {
    $type = makeBenefitType();

    makeEmployeeBenefit(['benefit_type_id' => $type->id]);

    $this->actingAs($this->admin)
        ->delete(route('benefit-types.destroy', $type));

    $this->assertDatabaseHas('benefit_types', [
        'id' => $type->id,
    ]);
});

it('forbids users without payroll.approve from creating a benefit type', function () {
    $auditor = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $auditor->assignRole('auditor');

    expect($auditor->can('payroll.approve'))->toBeFalse();

    $this->actingAs($auditor)
        ->post(route('benefit-types.store'), [
            'code' => 'TSTMED',
            'name' => 'Medical',
            'default_quota' => 10000000,
            'is_active' => true,
        ])
        ->assertForbidden();
});
