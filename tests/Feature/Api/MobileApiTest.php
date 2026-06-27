<?php

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\PayrollRun;
use App\Models\Payslip;
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

    // Pick an employee-linked user (seeder emails are randomised). Build payroll
    // + leave fixtures since the seeder itself does not generate payslips.
    $this->user = User::whereNotNull('employee_id')->firstOrFail();
    $this->user->update(['status' => 'active']);

    $run = PayrollRun::factory()->create();
    $this->payslip = Payslip::factory()->create([
        'run_id' => $run->id,
        'employee_id' => $this->user->employee_id,
    ]);
    $this->otherPayslip = Payslip::factory()->create(['run_id' => $run->id]);
    LeaveBalance::factory()->create(['employee_id' => $this->user->employee_id]);
});

it('logs in and returns a JWT with the user profile', function () {
    $this->postJson(route('api.auth.login'), [
        'email' => $this->user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'user' => ['id', 'name', 'email', 'roles', 'employee' => ['employee_no']],
        ]);
});

it('rejects wrong credentials', function () {
    $this->postJson(route('api.auth.login'), [
        'email' => $this->user->email,
        'password' => 'salah',
    ])->assertStatus(401);
});

it('requires authentication for protected routes', function () {
    $this->getJson(route('api.me.profile'))->assertStatus(401);
});

it('returns the authenticated employee profile', function () {
    $this->actingAs($this->user, 'api')
        ->getJson(route('api.me.profile'))
        ->assertOk()
        ->assertJsonPath('data.employee_no', $this->user->employee->employee_no);
});

it('lists only the employees own payslips', function () {
    $this->actingAs($this->user, 'api')
        ->getJson(route('api.me.payslips'))
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'period', 'net']]]);
});

it('blocks reading another employees payslip (IDOR)', function () {
    $this->actingAs($this->user, 'api')
        ->getJson(route('api.me.payslips.show', $this->otherPayslip))
        ->assertStatus(403);
});

it('cannot read another tenants payslip (tenant isolation)', function () {
    // Build a payslip that belongs entirely to a different tenant.
    $otherTenant = Tenant::factory()->create();
    app(CurrentTenant::class)->set($otherTenant);
    $foreignRun = PayrollRun::factory()->create();
    $foreignPayslip = Payslip::factory()->create(['run_id' => $foreignRun->id]);

    // Restore our tenant context, then try to reach the foreign payslip.
    app(CurrentTenant::class)->set($this->tenant);

    // TenantScope hides cross-tenant rows entirely → route binding 404s.
    $this->actingAs($this->user, 'api')
        ->getJson(route('api.me.payslips.show', $foreignPayslip))
        ->assertStatus(404);
});

it('returns leave balances and notifications scoped to the user', function () {
    $this->actingAs($this->user, 'api')
        ->getJson(route('api.me.leave.balances'))
        ->assertOk()
        ->assertJsonStructure(['data']);

    $this->actingAs($this->user, 'api')
        ->getJson(route('api.me.notifications'))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['unread']]);
});

it('marks all notifications read', function () {
    $this->actingAs($this->user, 'api')
        ->postJson(route('api.me.notifications.read-all'))
        ->assertOk();

    expect(
        Notification::where('user_id', $this->user->id)->whereNull('read_at')->count()
    )->toBe(0);
});

it('records a clock-in as a raw log and a daily summary', function () {
    $this->actingAs($this->user, 'api')
        ->postJson(route('api.me.attendance.clock'), [
            'type' => 'in',
            'latitude' => -6.2241,
            'longitude' => 106.8090,
            'face_confidence' => 0.95,
        ])
        ->assertCreated()
        ->assertJsonPath('data.is_suspicious', false)
        ->assertJsonPath('data.today.next_action', 'out');

    $this->assertDatabaseHas('attendance_logs', [
        'employee_id' => $this->user->employee_id,
        'type' => 'in',
        'source' => 'mobile',
    ]);
    $this->assertDatabaseHas('attendance_daily', [
        'employee_id' => $this->user->employee_id,
    ]);
});

it('flags a low-confidence face punch as suspicious', function () {
    $this->actingAs($this->user, 'api')
        ->postJson(route('api.me.attendance.clock'), ['type' => 'in', 'face_confidence' => 0.40])
        ->assertCreated()
        ->assertJsonPath('data.is_suspicious', true);
});

it('validates the clock type', function () {
    $this->actingAs($this->user, 'api')
        ->postJson(route('api.me.attendance.clock'), ['type' => 'jump'])
        ->assertStatus(422);
});

it('submits a leave request for the authenticated employee only', function () {
    $leaveType = LeaveType::firstOrFail();

    $this->actingAs($this->user, 'api')
        ->postJson(route('api.me.leave-requests.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-11',
            'reason' => 'Acara keluarga',
        ])
        ->assertCreated()
        ->assertJsonPath('data.days', 2);

    $this->assertDatabaseHas('leave_requests', [
        'employee_id' => $this->user->employee_id,
        'leave_type_id' => $leaveType->id,
        'days' => 2,
    ]);
});

it('submits an overtime request', function () {
    $this->actingAs($this->user, 'api')
        ->postJson(route('api.me.overtime-requests.store'), [
            'date' => '2026-07-01',
            'start_time' => '18:00',
            'end_time' => '20:30',
            'reason' => 'Closing bulanan',
        ])
        ->assertCreated()
        ->assertJsonPath('data.planned_minutes', 150);

    $this->assertDatabaseHas('overtime_requests', [
        'employee_id' => $this->user->employee_id,
        'planned_minutes' => 150,
    ]);
});

it('submits a reimbursement', function () {
    $this->actingAs($this->user, 'api')
        ->postJson(route('api.me.reimbursements.store'), [
            'category' => 'transport',
            'amount' => 75000,
        ])
        ->assertCreated()
        ->assertJsonPath('data.amount', 75000);

    $this->assertDatabaseHas('reimbursements', [
        'employee_id' => $this->user->employee_id,
        'amount' => 75000,
    ]);
});

it('submits a loan and lists it', function () {
    $this->actingAs($this->user, 'api')
        ->postJson(route('api.me.loans.store'), ['principal' => 12000000, 'tenor_months' => 12])
        ->assertCreated()
        ->assertJsonPath('data.installment', 1000000);

    $this->actingAs($this->user, 'api')
        ->getJson(route('api.me.loans'))
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'principal', 'installment', 'status']]]);
});

it('submits a work visit', function () {
    $this->actingAs($this->user, 'api')
        ->postJson(route('api.me.work-visits.store'), [
            'destination' => 'Surabaya',
            'purpose' => 'Audit cabang',
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-22',
            'estimated_cost' => 3500000,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('work_visits', [
        'employee_id' => $this->user->employee_id,
        'destination' => 'Surabaya',
    ]);
});

it('updates contact profile through maker-checker', function () {
    $this->actingAs($this->user, 'api')
        ->putJson(route('api.me.profile.update'), [
            'phone' => '081234567890',
            'address' => 'Jl. Test 99',
        ])
        ->assertOk()
        ->assertJsonStructure(['message', 'pending']);
});

it('registers a device for push notifications', function () {
    $this->actingAs($this->user, 'api')
        ->postJson(route('api.me.devices.register'), [
            'device_id' => 'PIXEL-7',
            'platform' => 'android',
            'fcm_token' => 'fcm_abc',
            'biometric_enabled' => true,
        ])
        ->assertOk();

    $this->assertDatabaseHas('device_registrations', [
        'user_id' => $this->user->id,
        'device_id' => 'PIXEL-7',
        'platform' => 'android',
    ]);
});

it('lets a manager list, approve, and bulk-approve the inbox', function () {
    $manager = User::role('manager')->whereNotNull('employee_id')->firstOrFail();
    $employee = User::role('employee')->whereNotNull('employee_id')
        ->where('id', '!=', $manager->id)->firstOrFail();
    $leaveType = LeaveType::firstOrFail();

    // Employee files two leave requests → land in the manager inbox.
    foreach (['2026-08-01' => '2026-08-01', '2026-08-05' => '2026-08-05'] as $start => $end) {
        $this->actingAs($employee, 'api')
            ->postJson(route('api.me.leave-requests.store'), [
                'leave_type_id' => $leaveType->id,
                'start_date' => $start,
                'end_date' => $end,
            ])->assertCreated();
    }

    $inbox = $this->actingAs($manager, 'api')
        ->getJson(route('api.mss.approvals'))
        ->assertOk()
        ->json('data');

    expect(count($inbox))->toBeGreaterThanOrEqual(2);

    $ids = collect($inbox)->pluck('id');

    $this->actingAs($manager, 'api')
        ->postJson(route('api.mss.approvals.act', $ids->first()), ['action' => 'approve'])
        ->assertOk();

    $this->actingAs($manager, 'api')
        ->postJson(route('api.mss.approvals.bulk'), [
            'ids' => $ids->slice(1)->values()->all(),
            'action' => 'approve',
        ])
        ->assertOk()
        ->assertJsonStructure(['processed', 'results']);
});

it('forbids a non-manager from the MSS inbox', function () {
    $employee = User::role('employee')->whereNotNull('employee_id')->firstOrFail();

    $this->actingAs($employee, 'api')
        ->getJson(route('api.mss.approvals'))
        ->assertStatus(403);
});

it('lists the managers team (reporting line)', function () {
    $manager = User::role('manager')->whereNotNull('employee_id')->firstOrFail();

    $this->actingAs($manager, 'api')
        ->getJson(route('api.mss.team'))
        ->assertOk()
        ->assertJsonStructure(['data']);
});
