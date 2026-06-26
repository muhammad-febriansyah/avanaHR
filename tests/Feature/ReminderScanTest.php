<?php

use App\Enums\EmploymentType;
use App\Models\DocumentReminder;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEmployment;
use App\Models\Notification;
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
    restoreReminderTenant();

    $this->employee = Employee::firstOrFail();
});

function restoreReminderTenant(): void
{
    app(CurrentTenant::class)->set(test()->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId(test()->tenant->id);
}

it('reminds HR of a document within its expiry lead time', function () {
    $document = EmployeeDocument::factory()->expiring()->create([ // expires in 10 days
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->employee->id, 'reminder_days' => 30,
    ]);

    $this->artisan('reminders:scan')->assertSuccessful();
    restoreReminderTenant();

    expect(DocumentReminder::where('employee_document_id', $document->id)->count())->toBe(1)
        ->and(Notification::where('type', 'document.expiring')->where('payload->document_id', $document->id)->exists())->toBeTrue();
});

it('does not remind for a document far from expiry', function () {
    $document = EmployeeDocument::factory()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->employee->id,
        'reminder_days' => 30, 'expired_at' => now()->addDays(200)->format('Y-m-d'),
    ]);

    $this->artisan('reminders:scan')->assertSuccessful();
    restoreReminderTenant();

    expect(DocumentReminder::where('employee_document_id', $document->id)->exists())->toBeFalse();
});

it('does not duplicate a document reminder on re-scan', function () {
    $document = EmployeeDocument::factory()->expiring()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->employee->id, 'reminder_days' => 30,
    ]);

    $this->artisan('reminders:scan')->assertSuccessful();
    $this->artisan('reminders:scan')->assertSuccessful();
    restoreReminderTenant();

    expect(DocumentReminder::where('employee_document_id', $document->id)->count())->toBe(1)
        ->and(Notification::where('type', 'document.expiring')->where('payload->document_id', $document->id)->count())
        ->toBeGreaterThan(0);
});

it('alerts HR of a fixed-term contract ending within the window', function () {
    $employment = EmployeeEmployment::factory()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->employee->id,
        'employment_type' => EmploymentType::Contract, 'effective_date' => now()->subYear(),
        'end_date' => now()->addDays(15)->format('Y-m-d'),
    ]);

    $this->artisan('reminders:scan')->assertSuccessful();
    restoreReminderTenant();

    expect(Notification::where('type', 'contract.expiring')->where('payload->employment_id', $employment->id)->exists())->toBeTrue();
});

it('ignores permanent and out-of-window contracts', function () {
    $permanent = EmployeeEmployment::factory()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->employee->id,
        'employment_type' => EmploymentType::Permanent, 'effective_date' => now()->subYear(), 'end_date' => null,
    ]);
    $farContract = EmployeeEmployment::factory()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->employee->id,
        'employment_type' => EmploymentType::Contract, 'effective_date' => now()->subYear(),
        'end_date' => now()->addDays(200)->format('Y-m-d'),
    ]);

    $this->artisan('reminders:scan')->assertSuccessful();
    restoreReminderTenant();

    expect(Notification::where('type', 'contract.expiring')->where('payload->employment_id', $permanent->id)->exists())->toBeFalse()
        ->and(Notification::where('type', 'contract.expiring')->where('payload->employment_id', $farContract->id)->exists())->toBeFalse();
});

it('does not duplicate a contract alert on re-scan', function () {
    $employment = EmployeeEmployment::factory()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->employee->id,
        'employment_type' => EmploymentType::Contract, 'effective_date' => now()->subYear(),
        'end_date' => now()->addDays(15)->format('Y-m-d'),
    ]);

    $recipients = User::role(['hr-admin', 'tenant-admin'])->count();

    $this->artisan('reminders:scan')->assertSuccessful();
    $this->artisan('reminders:scan')->assertSuccessful();
    restoreReminderTenant();

    expect(Notification::where('type', 'contract.expiring')->where('payload->employment_id', $employment->id)->count())
        ->toBe($recipients);
});
