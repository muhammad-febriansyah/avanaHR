<?php

use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Support\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('auto-fills tenant_id and isolates queries per tenant', function () {
    $alpha = Tenant::create(['name' => 'Alpha', 'slug' => 'alpha']);
    $beta = Tenant::create(['name' => 'Beta', 'slug' => 'beta']);

    $current = app(CurrentTenant::class);

    $current->set($alpha);
    $employee = Employee::create(['employee_no' => 'EMP001', 'first_name' => 'Andi']);

    expect($employee->tenant_id)->toBe($alpha->id)
        ->and(Employee::count())->toBe(1);

    // Switching tenant hides the other tenant's rows.
    $current->set($beta);
    expect(Employee::count())->toBe(0);

    // Super-admin path: removing the scope sees everything.
    expect(Employee::withoutGlobalScope(TenantScope::class)->count())->toBe(1);
});

it('applies no tenant constraint when no tenant is resolved', function () {
    $alpha = Tenant::create(['name' => 'Alpha', 'slug' => 'alpha']);

    app(CurrentTenant::class)->set($alpha);
    Employee::create(['employee_no' => 'EMP001', 'first_name' => 'Andi']);

    app(CurrentTenant::class)->forget();

    expect(Employee::count())->toBe(1);
});
