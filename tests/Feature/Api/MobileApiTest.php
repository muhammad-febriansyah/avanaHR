<?php

use App\Models\LeaveBalance;
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
