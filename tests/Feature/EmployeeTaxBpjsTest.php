<?php

use App\Models\Employee;
use App\Models\EmployeeBpjsProfile;
use App\Models\EmployeeTaxProfile;
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
    $this->employee = Employee::factory()->create(['tenant_id' => $this->tenant->id]);
});

it('renders the tax & BPJS page', function () {
    $this->actingAs($this->admin)
        ->get(route('employees.tax-bpjs.index', $this->employee))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/tax-bpjs')
            ->has('taxProfiles')
            ->has('bpjsProfiles')
            ->has('ptkpOptions'),
        );
});

it('adds a tax profile', function () {
    $this->actingAs($this->admin)
        ->post(route('employees.tax-profiles.store', $this->employee), [
            'effective_date' => '2026-01-01',
            'ptkp_status' => 'K/1',
            'tax_method' => 'ter',
            'beginning_ytd' => 0,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('employee_tax_profiles', [
        'employee_id' => $this->employee->id,
        'ptkp_status' => 'K/1',
    ]);
});

it('rejects an invalid PTKP status', function () {
    $this->actingAs($this->admin)
        ->post(route('employees.tax-profiles.store', $this->employee), [
            'effective_date' => '2026-01-01',
            'ptkp_status' => 'INVALID',
            'tax_method' => 'ter',
        ])
        ->assertSessionHasErrors('ptkp_status');
});

it('adds a BPJS profile with participation flags', function () {
    $this->actingAs($this->admin)
        ->post(route('employees.bpjs-profiles.store', $this->employee), [
            'effective_date' => '2026-01-01',
            'kesehatan_basis' => 8_000_000,
            'tk_basis' => 8_000_000,
            'kesehatan' => true, 'jht' => true, 'jkk' => false, 'jkm' => true, 'jp' => false,
        ])
        ->assertRedirect();

    $profile = EmployeeBpjsProfile::where('employee_id', $this->employee->id)->firstOrFail();
    expect($profile->participation_flags['kesehatan'])->toBeTrue();
    expect($profile->participation_flags['jkk'])->toBeFalse();
    expect((int) $profile->kesehatan_basis)->toBe(8_000_000);
});

it('deletes a tax profile', function () {
    $tp = EmployeeTaxProfile::create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->employee->id,
        'effective_date' => '2026-01-01', 'ptkp_status' => 'TK/0', 'tax_method' => 'ter', 'beginning_ytd' => 0,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('employees.tax-profiles.destroy', $tp))
        ->assertRedirect();

    $this->assertDatabaseMissing('employee_tax_profiles', ['id' => $tp->id]);
});

it('forbids users without payroll.view', function () {
    $plain = User::query()->where('tenant_id', $this->tenant->id)->get()
        ->first(fn (User $u) => ! $u->can('payroll.view'));

    $this->actingAs($plain)
        ->get(route('employees.tax-bpjs.index', $this->employee))
        ->assertForbidden();
});
