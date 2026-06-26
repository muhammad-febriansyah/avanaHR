<?php

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use App\Support\SensitiveData;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail(); // has view_sensitive
    $this->manager = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'manager'))->firstOrFail(); // employee.view only

    $this->employee = Employee::firstOrFail();
    $this->employee->update(['nik_ktp' => '1234567890123456', 'npwp' => '091234567890000']);
});

it('masks NIK helper keeping the last 4', function () {
    expect(SensitiveData::mask('1234567890123456'))->toBe('••••••••••••3456')
        ->and(SensitiveData::mask('ab'))->toBe('••')
        ->and(SensitiveData::mask(null))->toBeNull();
});

it('shows full sensitive data to a user with view_sensitive', function () {
    $this->actingAs($this->admin)
        ->get(route('employees.show', $this->employee))
        ->assertInertia(fn ($page) => $page
            ->where('employee.nik_ktp', '1234567890123456')
            ->where('employee.npwp', '091234567890000')
        );
});

it('masks sensitive data on the show page for a user without view_sensitive', function () {
    $this->actingAs($this->manager)
        ->get(route('employees.show', $this->employee))
        ->assertInertia(fn ($page) => $page
            ->where('employee.nik_ktp', '••••••••••••3456')
            ->where('employee.npwp', fn ($v) => str_contains((string) $v, '•'))
        );
});

it('masks sensitive data in the employee list for a user without view_sensitive', function () {
    $response = $this->actingAs($this->manager)->get(route('employees.index'));

    $response->assertInertia(fn ($page) => $page->where(
        'employees.data',
        fn ($rows) => collect($rows)
            ->filter(fn ($r) => $r['id'] === $this->employee->id)
            ->every(fn ($r) => str_contains((string) $r['nik_ktp'], '•')),
    ));
});
