<?php

use App\Models\ReportDefinition;
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

it('renders the builder index with the source catalog', function () {
    $this->actingAs($this->admin)
        ->get(route('report-builder.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('report-builder/index')
            ->has('definitions.data')
            ->has('sources'),
        );
});

it('saves a report definition and keeps only whitelisted columns', function () {
    $this->actingAs($this->admin)
        ->post(route('report-builder.store'), [
            'name' => 'Karyawan Aktif',
            'source' => 'employees',
            'columns' => ['employee_no', 'first_name', 'status'],
        ])
        ->assertRedirect();

    $definition = ReportDefinition::firstOrFail();

    expect($definition->source)->toBe('employees');
    expect($definition->columns)->toBe(['employee_no', 'first_name', 'status']);
    expect($definition->created_by)->toBe($this->admin->id);
});

it('rejects an unknown column', function () {
    $this->actingAs($this->admin)
        ->post(route('report-builder.store'), [
            'name' => 'Bad',
            'source' => 'employees',
            'columns' => ['password', 'secret'],
        ])
        ->assertSessionHasErrors('columns.0');
});

it('requires at least one column', function () {
    $this->actingAs($this->admin)
        ->post(route('report-builder.store'), [
            'name' => 'Empty',
            'source' => 'employees',
            'columns' => [],
        ])
        ->assertSessionHasErrors('columns');
});

it('runs a report and returns the selected columns', function () {
    $definition = ReportDefinition::factory()->create([
        'columns' => ['employee_no', 'first_name', 'status'],
    ]);

    $this->actingAs($this->admin)
        ->get(route('report-builder.run', $definition))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('report-builder/run')
            ->has('columns', 3)
            ->where('columns.0.key', 'employee_no')
            ->has('rows.data'),
        );
});

it('deletes a report definition', function () {
    $definition = ReportDefinition::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('report-builder.destroy', $definition))
        ->assertRedirect(route('report-builder.index'));

    $this->assertDatabaseMissing('report_definitions', ['id' => $definition->id]);
});

it('forbids users without report.view', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('report.view'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->get(route('report-builder.index'))
        ->assertForbidden();
});
