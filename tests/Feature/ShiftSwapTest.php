<?php

use App\Approvals\ApprovalEngine;
use App\Enums\ApprovalActionType;
use App\Enums\RequestStatus;
use App\Models\ApprovalFlow;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSwap;
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

    $this->engine = app(ApprovalEngine::class);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->manager = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'manager'))->firstOrFail();

    $this->emp1 = Employee::orderBy('id')->first();
    $this->emp2 = Employee::orderBy('id')->skip(1)->first();
    $this->shiftA = Shift::factory()->create(['tenant_id' => $this->tenant->id, 'code' => 'A1']);
    $this->shiftB = Shift::factory()->create(['tenant_id' => $this->tenant->id, 'code' => 'B1']);

    $this->schedA = Schedule::factory()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->emp1->id,
        'date' => '2026-07-01', 'shift_id' => $this->shiftA->id,
    ]);
    $this->schedB = Schedule::factory()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->emp2->id,
        'date' => '2026-07-02', 'shift_id' => $this->shiftB->id,
    ]);
});

function makeSwap(): ShiftSwap
{
    return ShiftSwap::factory()->create([
        'tenant_id' => test()->tenant->id,
        'requester_id' => test()->emp1->id, 'target_id' => test()->emp2->id,
        'date_a' => '2026-07-01', 'date_b' => '2026-07-02', 'status' => RequestStatus::Pending,
    ]);
}

function shiftSwapFlow(): void
{
    ApprovalFlow::where('transaction_type', 'shift_swap')->delete();
    $flow = ApprovalFlow::create(['transaction_type' => 'shift_swap', 'name' => 'Tukar Shift', 'is_active' => true]);
    $flow->steps()->create(['order' => 1, 'mode' => 'sequential', 'approver_type' => 'role', 'approver_ref' => 'manager', 'min_approvals' => 1]);
}

it('swaps the schedules on approval (auto-approve, no flow)', function () {
    $this->engine->submit(makeSwap(), $this->admin);

    expect((int) $this->schedA->fresh()->shift_id)->toBe($this->shiftB->id)
        ->and((int) $this->schedB->fresh()->shift_id)->toBe($this->shiftA->id);
});

it('holds the swap until approved when a flow exists', function () {
    shiftSwapFlow();
    $swap = makeSwap();
    $this->engine->submit($swap, $this->admin);

    // Not yet applied.
    expect((int) $this->schedA->fresh()->shift_id)->toBe($this->shiftA->id)
        ->and($swap->fresh()->status)->toBe(RequestStatus::Pending);

    $this->engine->act($swap->approvalRequest, $this->manager, ApprovalActionType::Approve);

    expect((int) $this->schedA->fresh()->shift_id)->toBe($this->shiftB->id)
        ->and((int) $this->schedB->fresh()->shift_id)->toBe($this->shiftA->id)
        ->and($swap->fresh()->status)->toBe(RequestStatus::Approved);
});

it('creates a swap via the controller and rejects same-employee', function () {
    $this->actingAs($this->admin)
        ->post(route('shift-swaps.store'), [
            'requester_id' => $this->emp1->id, 'target_id' => $this->emp2->id,
            'date_a' => '2026-07-01', 'date_b' => '2026-07-02',
        ])->assertRedirect();

    expect(ShiftSwap::where('requester_id', $this->emp1->id)->exists())->toBeTrue();

    $this->actingAs($this->admin)
        ->post(route('shift-swaps.store'), [
            'requester_id' => $this->emp1->id, 'target_id' => $this->emp1->id,
            'date_a' => '2026-07-01', 'date_b' => '2026-07-02',
        ])->assertSessionHasErrors('target_id');
});

it('renders a pending shift swap in the inbox without lazy-loading violation', function () {
    shiftSwapFlow();
    $this->engine->submit(makeSwap(), $this->admin);

    $this->actingAs($this->manager)
        ->get(route('approvals.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'requests',
            fn ($requests) => collect($requests)->contains(fn ($r) => str_contains((string) ($r['title'] ?? ''), 'Tukar Shift')),
        ));
});
