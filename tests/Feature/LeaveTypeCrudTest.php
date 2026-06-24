<?php

use App\Models\LeaveType;
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

function validLeaveTypePayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'CT',
        'name' => 'Cuti Tahunan',
        'is_paid' => true,
        'max_days_year' => 12,
        'allow_negative' => false,
    ], $overrides);
}

it('renders the leave types index', function () {
    $this->actingAs($this->admin)
        ->get(route('leave-types.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leave-types/index')
            ->has('leaveTypes'),
        );
});

it('creates a leave type', function () {
    $this->actingAs($this->admin)
        ->post(route('leave-types.store'), validLeaveTypePayload())
        ->assertRedirect();

    $this->assertDatabaseHas('leave_types', [
        'tenant_id' => $this->tenant->id,
        'code' => 'CT',
        'name' => 'Cuti Tahunan',
        'is_paid' => true,
    ]);
});

it('rejects a duplicate code within the tenant', function () {
    LeaveType::factory()->create(['code' => 'CT']);

    $this->actingAs($this->admin)
        ->post(route('leave-types.store'), validLeaveTypePayload(['code' => 'CT']))
        ->assertSessionHasErrors('code');
});

it('updates a leave type', function () {
    $type = LeaveType::factory()->create(['code' => 'CS', 'name' => 'Cuti Sakit']);

    $this->actingAs($this->admin)
        ->put(route('leave-types.update', $type), validLeaveTypePayload([
            'code' => 'CS',
            'name' => 'Cuti Sakit Berbayar',
        ]))
        ->assertRedirect();

    expect($type->fresh()->name)->toBe('Cuti Sakit Berbayar');
});

it('deletes a leave type', function () {
    $type = LeaveType::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('leave-types.destroy', $type))
        ->assertRedirect();

    $this->assertSoftDeleted($type);
});

it('forbids users without setting.manage', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')->firstOrFail();

    $this->actingAs($employee)
        ->post(route('leave-types.store'), validLeaveTypePayload())
        ->assertForbidden();
});
