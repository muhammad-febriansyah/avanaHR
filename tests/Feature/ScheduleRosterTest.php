<?php

use App\Actions\Schedule\GenerateScheduleAction;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftPattern;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Carbon\CarbonImmutable;
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
    $this->employeeUser = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'employee'))->firstOrFail();
    $this->employee = Employee::firstOrFail();
    $this->shiftA = Shift::factory()->create(['tenant_id' => $this->tenant->id, 'code' => 'A1']);
    $this->shiftB = Shift::factory()->create(['tenant_id' => $this->tenant->id, 'code' => 'B1']);
});

it('generates roster rows from a cyclic pattern, skipping off days', function () {
    // 3-day cycle: A, B, off. Over 6 days → A,B,_,A,B,_ = 4 rows.
    $pattern = ShiftPattern::factory()->create([
        'tenant_id' => $this->tenant->id,
        'config' => ['days' => [$this->shiftA->id, $this->shiftB->id, null]],
    ]);

    $written = app(GenerateScheduleAction::class)->execute(
        $pattern,
        [$this->employee->id],
        CarbonImmutable::parse('2026-07-01'),
        CarbonImmutable::parse('2026-07-06'),
    );

    expect($written)->toBe(4)
        ->and(Schedule::where('employee_id', $this->employee->id)->count())->toBe(4)
        ->and(Schedule::where('employee_id', $this->employee->id)->whereDate('date', '2026-07-01')->value('shift_id'))->toBe($this->shiftA->id)
        ->and(Schedule::where('employee_id', $this->employee->id)->whereDate('date', '2026-07-02')->value('shift_id'))->toBe($this->shiftB->id)
        // 2026-07-03 = off (no row)
        ->and(Schedule::where('employee_id', $this->employee->id)->whereDate('date', '2026-07-03')->exists())->toBeFalse()
        ->and(Schedule::where('employee_id', $this->employee->id)->whereDate('date', '2026-07-04')->value('shift_id'))->toBe($this->shiftA->id);
});

it('is idempotent — re-generating overwrites instead of duplicating', function () {
    $pattern = ShiftPattern::factory()->create([
        'tenant_id' => $this->tenant->id,
        'config' => ['days' => [$this->shiftA->id]],
    ]);
    $action = app(GenerateScheduleAction::class);

    $action->execute($pattern, [$this->employee->id], CarbonImmutable::parse('2026-07-01'), CarbonImmutable::parse('2026-07-03'));
    $action->execute($pattern, [$this->employee->id], CarbonImmutable::parse('2026-07-01'), CarbonImmutable::parse('2026-07-03'));

    expect(Schedule::where('employee_id', $this->employee->id)->count())->toBe(3);
});

it('generates the roster via the controller for an authorized user', function () {
    $pattern = ShiftPattern::factory()->create([
        'tenant_id' => $this->tenant->id,
        'config' => ['days' => [$this->shiftA->id]],
    ]);

    $this->actingAs($this->admin)
        ->post(route('schedules.generate'), [
            'pattern_id' => $pattern->id,
            'employee_ids' => [$this->employee->id],
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
        ])
        ->assertRedirect();

    expect(Schedule::where('employee_id', $this->employee->id)->count())->toBe(5);
});

it('assigns a single shift via the controller (idempotent per employee+date)', function () {
    $this->actingAs($this->admin)
        ->post(route('schedules.store'), [
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shiftA->id,
            'date' => '2026-07-10',
        ])->assertRedirect();

    // Re-assign same date with a different shift overwrites.
    $this->actingAs($this->admin)
        ->post(route('schedules.store'), [
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shiftB->id,
            'date' => '2026-07-10',
        ])->assertRedirect();

    expect(Schedule::where('employee_id', $this->employee->id)->whereDate('date', '2026-07-10')->count())->toBe(1)
        ->and(Schedule::where('employee_id', $this->employee->id)->whereDate('date', '2026-07-10')->value('shift_id'))->toBe($this->shiftB->id);
});

it('stores a shift pattern with its day cycle', function () {
    $this->actingAs($this->admin)
        ->post(route('shift-patterns.store'), [
            'name' => 'Rotasi A-B-Off',
            'type' => 'cyclic',
            'days' => [$this->shiftA->id, $this->shiftB->id, null],
        ])->assertRedirect();

    $pattern = ShiftPattern::where('name', 'Rotasi A-B-Off')->firstOrFail();
    expect($pattern->config['days'])->toBe([$this->shiftA->id, $this->shiftB->id, null]);
});

it('blocks roster pages for employees without attendance permission', function () {
    $this->actingAs($this->employeeUser)->get(route('schedules.index'))->assertForbidden();
    $this->actingAs($this->employeeUser)->get(route('shift-patterns.index'))->assertForbidden();
});
