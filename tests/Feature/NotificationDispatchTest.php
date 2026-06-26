<?php

use App\Approvals\ApprovalNotifier;
use App\Mail\ApprovalNotificationMail;
use App\Models\Notification;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
});

it('creates a pending email notification', function () {
    app(ApprovalNotifier::class)->toUser($this->tenant->id, $this->admin->id, 'approval.assigned', ['title' => 'Lembur X']);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $this->admin->id,
        'channel' => 'email',
        'type' => 'approval.assigned',
        'status' => 'pending',
    ]);
});

it('dispatches pending email notifications and marks them sent', function () {
    Mail::fake();

    $notification = Notification::create([
        'tenant_id' => $this->tenant->id, 'user_id' => $this->admin->id,
        'channel' => 'email', 'type' => 'approval.assigned',
        'payload' => ['title' => 'Lembur X'], 'status' => 'pending',
    ]);

    $this->artisan('notifications:dispatch')->assertSuccessful();

    Mail::assertSent(ApprovalNotificationMail::class);
    expect($notification->fresh()->status)->toBe('sent');
    expect($notification->fresh()->sent_at)->not->toBeNull();
});

it('renders the email body without view errors', function () {
    $notification = Notification::create([
        'tenant_id' => $this->tenant->id, 'user_id' => $this->admin->id,
        'channel' => 'email', 'type' => 'approval.assigned',
        'payload' => ['title' => 'Lembur X'], 'status' => 'pending',
    ]);

    $html = (new ApprovalNotificationMail($notification))->render();

    expect($html)->toContain('persetujuan');
    expect($html)->toContain('Lembur X');
});

it('skips a recipient without an email', function () {
    Mail::fake();

    $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'email' => '']);
    $notification = Notification::create([
        'tenant_id' => $this->tenant->id, 'user_id' => $user->id,
        'channel' => 'email', 'type' => 'approval.assigned', 'status' => 'pending',
    ]);

    $this->artisan('notifications:dispatch')->assertSuccessful();

    Mail::assertNothingSent();
    expect($notification->fresh()->status)->toBe('skipped');
});
