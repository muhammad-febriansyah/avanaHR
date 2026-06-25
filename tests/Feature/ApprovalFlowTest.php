<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
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

it('renders the flow index', function () {
    $this->actingAs($this->admin)
        ->get(route('approval-flows.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approval-flows/index')
            ->has('flows.data')
            ->has('transactionTypes'),
        );
});

it('creates a flow and redirects to its editor', function () {
    $this->actingAs($this->admin)
        ->post(route('approval-flows.store'), [
            'name' => 'Persetujuan Cuti 2 Level',
            'transaction_type' => 'leave',
            'is_active' => true,
        ])
        ->assertRedirect();

    $flow = ApprovalFlow::firstOrFail();

    expect($flow->name)->toBe('Persetujuan Cuti 2 Level');
    expect($flow->transaction_type)->toBe('leave');
    expect($flow->is_active)->toBeTrue();
});

it('shows a flow with its ordered steps', function () {
    $flow = ApprovalFlow::factory()->create();
    ApprovalFlowStep::factory()->create(['flow_id' => $flow->id, 'order' => 1]);

    $this->actingAs($this->admin)
        ->get(route('approval-flows.show', $flow))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approval-flows/show')
            ->where('flow.id', $flow->id)
            ->has('flow.steps', 1),
        );
});

it('appends a step with the next order', function () {
    $flow = ApprovalFlow::factory()->create();
    ApprovalFlowStep::factory()->create(['flow_id' => $flow->id, 'order' => 1]);

    $this->actingAs($this->admin)
        ->post(route('approval-flows.steps.store', $flow), [
            'approver_type' => 'role',
            'approver_ref' => 'finance',
            'mode' => 'any',
            'min_approvals' => 1,
            'sla_hours' => 8,
            'allow_delegate' => true,
        ])
        ->assertRedirect();

    $step = $flow->steps()->orderByDesc('order')->first();

    expect($step->order)->toBe(2);
    expect($step->approver_ref)->toBe('finance');
});

it('validates step approver type', function () {
    $flow = ApprovalFlow::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('approval-flows.steps.store', $flow), [
            'approver_type' => 'invalid',
            'mode' => 'all',
            'min_approvals' => 1,
        ])
        ->assertSessionHasErrors('approver_type');
});

it('toggles the flow active state', function () {
    $flow = ApprovalFlow::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('approval-flows.update', $flow))
        ->assertRedirect();

    expect($flow->fresh()->is_active)->toBeFalse();
});

it('deletes a step', function () {
    $flow = ApprovalFlow::factory()->create();
    $step = ApprovalFlowStep::factory()->create(['flow_id' => $flow->id]);

    $this->actingAs($this->admin)
        ->delete(route('approval-flows.steps.destroy', $step))
        ->assertRedirect();

    $this->assertDatabaseMissing('approval_flow_steps', ['id' => $step->id]);
});

it('deletes a flow with its steps', function () {
    $flow = ApprovalFlow::factory()->create();
    ApprovalFlowStep::factory()->create(['flow_id' => $flow->id]);

    $this->actingAs($this->admin)
        ->delete(route('approval-flows.destroy', $flow))
        ->assertRedirect(route('approval-flows.index'));

    $this->assertDatabaseMissing('approval_flows', ['id' => $flow->id]);
    $this->assertDatabaseMissing('approval_flow_steps', ['flow_id' => $flow->id]);
});

it('forbids users without setting.manage', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('setting.manage'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->get(route('approval-flows.index'))
        ->assertForbidden();
});
