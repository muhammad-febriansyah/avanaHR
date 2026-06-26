<?php

use App\Approvals\ApprovalEngine;
use App\Models\ApprovalFlow;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
});

it('saves routing conditions, omitting empty fields', function () {
    $flow = ApprovalFlow::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('approval-flows.conditions', $flow), [
            'amount_min' => 1000000,
            'grade_in' => ['G1'],
            'department_id' => null,
            'branch_id' => null,
        ])
        ->assertRedirect();

    $conditions = $flow->fresh()->conditions;

    expect($conditions)->toBe([
        'amount_min' => 1000000,
        'grade_in' => ['G1'],
    ]);
});

it('nulls the conditions column when all fields are empty', function () {
    $flow = ApprovalFlow::factory()->create(['conditions' => ['amount_min' => 500000]]);

    $this->actingAs($this->admin)
        ->patch(route('approval-flows.conditions', $flow), [
            'amount_min' => null,
            'amount_max' => null,
            'grade_in' => [],
            'department_id' => null,
            'branch_id' => null,
        ])
        ->assertRedirect();

    expect($flow->fresh()->conditions)->toBeNull();
});

it('produces conditions the engine evaluates correctly', function () {
    $flow = ApprovalFlow::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('approval-flows.conditions', $flow), [
            'amount_min' => 1000000,
        ])
        ->assertRedirect();

    $engine = app(ApprovalEngine::class);
    $conditions = $flow->fresh()->conditions;

    expect($engine->conditionsMatch($conditions, ['amount' => 5000000]))->toBeTrue();
    expect($engine->conditionsMatch($conditions, ['amount' => 500000]))->toBeFalse();
});

it('exposes conditions and option lists on the show page', function () {
    $flow = ApprovalFlow::factory()->create(['conditions' => ['amount_min' => 250000]]);

    $this->actingAs($this->admin)
        ->get(route('approval-flows.show', $flow))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('approval-flows/show')
            ->where('flow.conditions.amount_min', 250000)
            ->has('gradeOptions')
            ->has('branchOptions'),
        );
});

it('forbids users without setting.manage from editing conditions', function () {
    $flow = ApprovalFlow::factory()->create();

    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('setting.manage'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->patch(route('approval-flows.conditions', $flow), ['amount_min' => 1000])
        ->assertForbidden();
});
