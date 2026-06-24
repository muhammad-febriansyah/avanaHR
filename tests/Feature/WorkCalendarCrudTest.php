<?php

use App\Models\Holiday;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkCalendar;
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

it('renders the calendars index', function () {
    $this->actingAs($this->admin)
        ->get(route('work-calendars.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendars/index')
            ->has('calendars')
            ->has('holidays')
            ->has('selectedCalendarId'),
        );
});

it('creates a work calendar', function () {
    $this->actingAs($this->admin)
        ->post(route('work-calendars.store'), ['name' => 'Kalender Pabrik', 'is_default' => false])
        ->assertRedirect();

    $this->assertDatabaseHas('work_calendars', [
        'tenant_id' => $this->tenant->id,
        'name' => 'Kalender Pabrik',
        'is_default' => false,
    ]);
});

it('keeps only one default calendar when creating a default', function () {
    $existingDefault = WorkCalendar::where('is_default', true)->firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('work-calendars.store'), ['name' => 'Kalender Baru', 'is_default' => true])
        ->assertRedirect();

    expect($existingDefault->fresh()->is_default)->toBeFalse();
    expect(WorkCalendar::where('is_default', true)->count())->toBe(1);
});

it('promotes a calendar to default and demotes the previous one', function () {
    $previousDefault = WorkCalendar::where('is_default', true)->firstOrFail();
    $calendar = WorkCalendar::factory()->create(['is_default' => false]);

    $this->actingAs($this->admin)
        ->put(route('work-calendars.default', $calendar))
        ->assertRedirect();

    expect($calendar->fresh()->is_default)->toBeTrue();
    expect($previousDefault->fresh()->is_default)->toBeFalse();
});

it('blocks deleting the default calendar', function () {
    $default = WorkCalendar::where('is_default', true)->firstOrFail();

    $this->actingAs($this->admin)
        ->delete(route('work-calendars.destroy', $default))
        ->assertRedirect();

    $this->assertNotSoftDeleted($default);
});

it('deletes a non-default calendar', function () {
    $calendar = WorkCalendar::factory()->create(['is_default' => false]);

    $this->actingAs($this->admin)
        ->delete(route('work-calendars.destroy', $calendar))
        ->assertRedirect();

    $this->assertSoftDeleted($calendar);
});

it('creates a holiday on a calendar', function () {
    $calendar = WorkCalendar::factory()->create(['is_default' => false]);

    $this->actingAs($this->admin)
        ->post(route('holidays.store'), [
            'calendar_id' => $calendar->id,
            'date' => '2026-08-17',
            'name' => 'Hari Kemerdekaan',
            'is_national' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('holidays', [
        'tenant_id' => $this->tenant->id,
        'calendar_id' => $calendar->id,
        'date' => '2026-08-17',
        'name' => 'Hari Kemerdekaan',
    ]);
});

it('rejects a duplicate holiday date in the same calendar', function () {
    $calendar = WorkCalendar::factory()->create(['is_default' => false]);
    Holiday::factory()->create(['calendar_id' => $calendar->id, 'date' => '2026-01-01']);

    $this->actingAs($this->admin)
        ->post(route('holidays.store'), [
            'calendar_id' => $calendar->id,
            'date' => '2026-01-01',
            'name' => 'Tahun Baru',
            'is_national' => true,
        ])
        ->assertSessionHasErrors('date');
});

it('deletes a holiday', function () {
    $calendar = WorkCalendar::factory()->create(['is_default' => false]);
    $holiday = Holiday::factory()->create(['calendar_id' => $calendar->id]);

    $this->actingAs($this->admin)
        ->delete(route('holidays.destroy', $holiday))
        ->assertRedirect();

    $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
});

it('forbids users without employee.create from creating calendars', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->firstOrFail();

    $this->actingAs($employee)
        ->post(route('work-calendars.store'), ['name' => 'Nope', 'is_default' => false])
        ->assertForbidden();
});
