<?php

use App\Models\Employee;
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
});

function validEmployeePayload(array $overrides = []): array
{
    return array_merge([
        'employee_no' => 'EMP90001',
        'first_name' => 'Siti',
        'last_name' => 'Rahma',
        'status' => 'active',
    ], $overrides);
}

it('renders the employee index', function () {
    $this->actingAs($this->admin)
        ->get(route('employees.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/index')
            ->has('employees.data')
            ->has('statuses'),
        );
});

it('creates an employee scoped to the tenant', function () {
    $this->actingAs($this->admin)
        ->post(route('employees.store'), validEmployeePayload())
        ->assertRedirect(route('employees.index'));

    $this->assertDatabaseHas('employees', [
        'employee_no' => 'EMP90001',
        'tenant_id' => $this->tenant->id,
    ]);
});

it('rejects invalid input with validation errors', function () {
    $this->actingAs($this->admin)
        ->post(route('employees.store'), ['first_name' => '', 'employee_no' => ''])
        ->assertSessionHasErrors(['employee_no', 'first_name', 'status']);
});

it('updates an employee', function () {
    $employee = Employee::create(validEmployeePayload(['employee_no' => 'EMP90002']));

    $this->actingAs($this->admin)
        ->put(route('employees.update', $employee), validEmployeePayload([
            'employee_no' => 'EMP90002',
            'first_name' => 'Updated',
        ]))
        ->assertRedirect(route('employees.index'));

    expect($employee->fresh()->first_name)->toBe('Updated');
});

it('soft deletes an employee', function () {
    $employee = Employee::create(validEmployeePayload(['employee_no' => 'EMP90003']));

    $this->actingAs($this->admin)
        ->delete(route('employees.destroy', $employee))
        ->assertRedirect(route('employees.index'));

    $this->assertSoftDeleted('employees', ['id' => $employee->id]);
});

it('forbids users without the employee permission', function () {
    $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $staff->assignRole('employee');

    $this->actingAs($staff)
        ->get(route('employees.index'))
        ->assertForbidden();

    $this->actingAs($staff)
        ->post(route('employees.store'), validEmployeePayload())
        ->assertForbidden();
});
