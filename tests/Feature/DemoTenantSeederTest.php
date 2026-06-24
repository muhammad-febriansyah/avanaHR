<?php

use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

it('seeds a demo tenant with organization, workforce and rbac', function () {
    $this->seed(DemoTenantSeeder::class);

    expect(Tenant::count())->toBe(1);

    $tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

    expect(Employee::count())->toBe(21)
        ->and(EmployeeEmployment::count())->toBe(20);

    $admin = User::where('email', 'admin@avanahr.id')->firstOrFail();

    expect($admin->tenant_id)->toBe($tenant->id)
        ->and($admin->employee_id)->not->toBeNull()
        ->and($admin->hasRole('hr-admin'))->toBeTrue();

    // Every seeded tenant user has an @avanahr.id email and an avatar URL.
    $users = User::where('tenant_id', $tenant->id)->get();
    expect($users)->toHaveCount(21)
        ->and($users->every(fn ($u): bool => str_ends_with($u->email, '@avanahr.id')))->toBeTrue()
        ->and($users->every(fn ($u): bool => filled($u->avatar_url)))->toBeTrue();
});
