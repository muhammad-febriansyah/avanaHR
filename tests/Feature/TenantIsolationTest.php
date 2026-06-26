<?php

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\PayrollComponent;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Tenant 1 = the fully-seeded demo tenant (admin = hr-admin).
    $this->seed(DemoTenantSeeder::class);
    $this->tenant1 = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant1);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant1->id);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();

    // Tenant 2 = a separate tenant with its own data (no global-scope helper:
    // we set tenant_id explicitly so the rows truly belong to tenant 2).
    $this->tenant2 = Tenant::factory()->create();
    $this->t2Employee = Employee::factory()->create(['tenant_id' => $this->tenant2->id]);
    $this->t2Component = PayrollComponent::create([
        'tenant_id' => $this->tenant2->id, 'code' => 'T2', 'name' => 'T2',
        'type' => 'earning', 'calc_type' => 'fixed', 'formula' => null,
        'is_taxable' => true, 'is_bpjs_base' => false,
    ]);
    $this->t2LeaveType = LeaveType::create([
        'tenant_id' => $this->tenant2->id, 'code' => 'T2', 'name' => 'Cuti T2',
        'is_paid' => true, 'max_days_year' => 12, 'allow_negative' => false,
    ]);
});

it('cannot view another tenant employee (404)', function () {
    $this->actingAs($this->admin)
        ->get(route('employees.show', $this->t2Employee->id))
        ->assertNotFound();
});

it('cannot update another tenant employee (404)', function () {
    $this->actingAs($this->admin)
        ->put(route('employees.update', $this->t2Employee->id), ['first_name' => 'Hacked'])
        ->assertNotFound();

    expect($this->t2Employee->fresh()->first_name)->not->toBe('Hacked');
});

it('cannot delete another tenant employee (404)', function () {
    $this->actingAs($this->admin)
        ->delete(route('employees.destroy', $this->t2Employee->id))
        ->assertNotFound();

    expect(Employee::withoutGlobalScopes()->find($this->t2Employee->id))->not->toBeNull();
});

it('the employee list never includes another tenant', function () {
    $this->actingAs($this->admin)
        ->get(route('employees.index'))
        ->assertInertia(fn ($page) => $page->where(
            'employees.data',
            fn ($rows) => collect($rows)->every(fn ($r) => $r['id'] !== $this->t2Employee->id),
        ));
});

it('cannot update another tenant payroll component (404)', function () {
    $this->actingAs($this->admin)
        ->put(route('payroll-components.update', $this->t2Component->id), ['code' => 'X', 'name' => 'X', 'type' => 'earning', 'calc_type' => 'fixed'])
        ->assertNotFound();
});

it('cannot delete another tenant leave type (404)', function () {
    $this->actingAs($this->admin)
        ->delete(route('leave-types.destroy', $this->t2LeaveType->id))
        ->assertNotFound();

    expect(LeaveType::withoutGlobalScopes()->find($this->t2LeaveType->id))->not->toBeNull();
});

it('scopes Eloquent reads to the current tenant', function () {
    // Under tenant 1 context, tenant 2 rows are invisible.
    expect(Employee::find($this->t2Employee->id))->toBeNull()
        ->and(PayrollComponent::find($this->t2Component->id))->toBeNull()
        ->and(Employee::query()->where('tenant_id', $this->tenant2->id)->count())->toBe(0);
});

it('stamps new records with the acting tenant, not another', function () {
    app(CurrentTenant::class)->set($this->tenant1);
    $created = LeaveType::create([
        'code' => 'NEW', 'name' => 'Baru', 'is_paid' => true, 'max_days_year' => 5, 'allow_negative' => false,
    ]);

    expect((int) $created->tenant_id)->toBe($this->tenant1->id);
});
