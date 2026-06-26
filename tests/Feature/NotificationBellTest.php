<?php

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
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->other = User::query()->where('id', '!=', $this->admin->id)
        ->where('tenant_id', $this->tenant->id)->firstOrFail();
});

function notif(User $user, ?string $readAt = null): Notification
{
    return Notification::create([
        'tenant_id' => test()->tenant->id, 'user_id' => $user->id,
        'channel' => 'inapp', 'type' => 'approval.assigned',
        'payload' => ['title' => 'Pengajuan menunggu'], 'status' => 'sent',
        'read_at' => $readAt,
    ]);
}

it('shares the unread count + items to the topbar bell', function () {
    notif($this->admin);
    notif($this->admin);

    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('notifications.unread', 2)
            ->has('notifications.items', 2)
        );
});

it('marks a single notification read', function () {
    $notification = notif($this->admin);

    $this->actingAs($this->admin)
        ->post(route('notifications.read', $notification))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks all notifications read', function () {
    notif($this->admin);
    notif($this->admin);

    $this->actingAs($this->admin)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect(Notification::where('user_id', $this->admin->id)->whereNull('read_at')->count())->toBe(0);
});

it('forbids marking another user notification read', function () {
    $notification = notif($this->other);

    $this->actingAs($this->admin)
        ->post(route('notifications.read', $notification))
        ->assertForbidden();

    expect($notification->fresh()->read_at)->toBeNull();
});
