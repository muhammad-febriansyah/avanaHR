<?php

use App\Models\Employee;
use App\Models\HrTicket;
use App\Models\HrTicketMessage;
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
    $this->employee = Employee::firstOrFail();
});

function ticketPayload(array $overrides = []): array
{
    return array_merge([
        'subject' => 'Pertanyaan slip gaji',
        'category' => 'payroll',
        'priority' => 'high',
        'employee_id' => test()->employee->id,
        'body' => 'Mohon penjelasan komponen potongan.',
    ], $overrides);
}

it('renders the ticket index', function () {
    $this->actingAs($this->admin)
        ->get(route('hr-tickets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('hr-tickets/index')
            ->has('tickets.data')
            ->has('statuses')
            ->has('priorities'),
        );
});

it('creates a ticket with an opening message and a ticket number', function () {
    $this->actingAs($this->admin)
        ->post(route('hr-tickets.store'), ticketPayload())
        ->assertRedirect();

    $ticket = HrTicket::where('subject', 'Pertanyaan slip gaji')->firstOrFail();

    expect($ticket->ticket_no)->toStartWith('TKT-');
    expect($ticket->status)->toBe('open');
    expect($ticket->messages()->count())->toBe(1);
    expect($ticket->messages()->first()->body)->toBe('Mohon penjelasan komponen potongan.');
});

it('validates required fields on create', function () {
    $this->actingAs($this->admin)
        ->post(route('hr-tickets.store'), ticketPayload(['subject' => '', 'body' => '']))
        ->assertSessionHasErrors(['subject', 'body']);
});

it('shows a ticket with its message thread', function () {
    $ticket = HrTicket::factory()->create(['employee_id' => $this->employee->id]);
    HrTicketMessage::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $this->admin->id]);

    $this->actingAs($this->admin)
        ->get(route('hr-tickets.show', $ticket))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('hr-tickets/show')
            ->where('ticket.id', $ticket->id)
            ->has('ticket.messages', 1),
        );
});

it('adds a reply to a ticket', function () {
    $ticket = HrTicket::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->post(route('hr-tickets.reply', $ticket), ['body' => 'Sedang kami cek.'])
        ->assertRedirect();

    expect($ticket->messages()->count())->toBe(1);
});

it('reopens a resolved ticket when a reply arrives', function () {
    $ticket = HrTicket::factory()->status('resolved')->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->post(route('hr-tickets.reply', $ticket), ['body' => 'Masih ada masalah.'])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe('in_progress');
});

it('updates status and stamps resolved_at', function () {
    $ticket = HrTicket::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->patch(route('hr-tickets.update', $ticket), ['status' => 'resolved', 'priority' => 'high'])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe('resolved');
    expect($ticket->fresh()->resolved_at)->not->toBeNull();
});

it('forbids users without employee.view', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('employee.view'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->get(route('hr-tickets.index'))
        ->assertForbidden();
});
