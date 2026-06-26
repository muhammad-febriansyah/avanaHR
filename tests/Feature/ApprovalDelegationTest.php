<?php

use App\Models\ApprovalDelegation;
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
    $this->manager = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
        ->firstOrFail();
});

it('renders the delegation page for an approver', function () {
    $this->actingAs($this->admin)
        ->get(route('approval-delegations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approval-delegations/index')
            ->has('delegations')
            ->has('users')
            ->has('transactionTypes'),
        );
});

it('creates a delegation scoped to the current user', function () {
    $this->actingAs($this->admin)
        ->post(route('approval-delegations.store'), [
            'to_user_id' => $this->manager->id,
            'transaction_type' => 'leave',
        ])
        ->assertRedirect();

    $delegation = ApprovalDelegation::firstOrFail();

    expect($delegation->from_user_id)->toBe($this->admin->id);
    expect($delegation->to_user_id)->toBe($this->manager->id);
    expect($delegation->transaction_types)->toBe(['leave']);
});

it('stores null transaction types when delegating all', function () {
    $this->actingAs($this->admin)
        ->post(route('approval-delegations.store'), [
            'to_user_id' => $this->manager->id,
            'transaction_type' => 'all',
        ])
        ->assertRedirect();

    expect(ApprovalDelegation::firstOrFail()->transaction_types)->toBeNull();
});

it('rejects delegating to yourself', function () {
    $this->actingAs($this->admin)
        ->post(route('approval-delegations.store'), [
            'to_user_id' => $this->admin->id,
            'transaction_type' => 'all',
        ])
        ->assertSessionHasErrors('to_user_id');
});

it('deletes own delegation', function () {
    $delegation = ApprovalDelegation::create([
        'from_user_id' => $this->admin->id,
        'to_user_id' => $this->manager->id,
        'transaction_types' => null,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('approval-delegations.destroy', $delegation))
        ->assertRedirect();

    $this->assertDatabaseMissing('approval_delegations', ['id' => $delegation->id]);
});

it('forbids deleting another user delegation', function () {
    $delegation = ApprovalDelegation::create([
        'from_user_id' => $this->manager->id,
        'to_user_id' => $this->admin->id,
        'transaction_types' => null,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('approval-delegations.destroy', $delegation))
        ->assertForbidden();

    $this->assertDatabaseHas('approval_delegations', ['id' => $delegation->id]);
});

it('forbids users without approval.act', function () {
    $plain = User::query()
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('approval.act'));

    $this->actingAs($plain)->get(route('approval-delegations.index'))->assertForbidden();
});
