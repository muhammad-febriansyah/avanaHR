<?php

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkVisit;
use App\Models\WorkVisitReport;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

/**
 * @param  array<string, mixed>  $overrides
 */
function makeAttachmentWorkVisit(array $overrides = []): WorkVisit
{
    return WorkVisit::create(array_merge([
        'tenant_id' => test()->tenant->id,
        'employee_id' => test()->employee->id,
        'destination' => 'Surabaya',
        'purpose' => 'Meeting klien',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'transport_mode' => 'pesawat',
        'estimated_cost' => 1500000,
        'status' => 'approved',
    ], $overrides));
}

it('stores a report attachment under the tenant-scoped visits path', function () {
    Storage::fake('public');

    $visit = makeAttachmentWorkVisit();

    $this->actingAs($this->admin)
        ->post(route('work-visits.reports.store', $visit), [
            'visited_at' => now()->toDateString(),
            'location' => 'Kantor Klien',
            'notes' => 'Diskusi requirement.',
            'attachment' => UploadedFile::fake()->create('bukti.pdf', 150, 'application/pdf'),
        ])
        ->assertRedirect();

    $report = WorkVisitReport::where('work_visit_id', $visit->id)->firstOrFail();

    expect($report->attachment_path)->not->toBeNull();
    expect($report->attachment_path)->toStartWith('visits/'.$this->tenant->id.'/');
    Storage::disk('public')->assertExists($report->attachment_path);
});

it('keeps the link behavior when no file is uploaded', function () {
    Storage::fake('public');

    $visit = makeAttachmentWorkVisit();

    $this->actingAs($this->admin)
        ->post(route('work-visits.reports.store', $visit), [
            'visited_at' => now()->toDateString(),
            'location' => 'Kantor Klien',
            'attachment_path' => 'https://drive.example.com/file/123',
        ])
        ->assertRedirect();

    $report = WorkVisitReport::where('work_visit_id', $visit->id)->firstOrFail();

    expect($report->attachment_path)->toBe('https://drive.example.com/file/123');
});

it('rejects a report attachment with a disallowed mime type', function () {
    Storage::fake('public');

    $visit = makeAttachmentWorkVisit();

    $this->actingAs($this->admin)
        ->post(route('work-visits.reports.store', $visit), [
            'visited_at' => now()->toDateString(),
            'location' => 'Kantor Klien',
            'attachment' => UploadedFile::fake()->create('script.exe', 100, 'application/octet-stream'),
        ])
        ->assertSessionHasErrors('attachment');

    expect(WorkVisitReport::where('work_visit_id', $visit->id)->exists())->toBeFalse();
});

it('exposes a download url for a stored attachment on the show page', function () {
    Storage::fake('public');

    $visit = makeAttachmentWorkVisit();

    $this->actingAs($this->admin)
        ->post(route('work-visits.reports.store', $visit), [
            'visited_at' => now()->toDateString(),
            'location' => 'Kantor Klien',
            'attachment' => UploadedFile::fake()->image('bukti.png', 200, 120),
        ])
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->get(route('work-visits.show', $visit))
        ->assertInertia(fn (Assert $page) => $page
            ->where('workVisit.reports.0.attachment_url', fn ($url) => is_string($url) && str_contains($url, 'visits/'.$this->tenant->id.'/')),
        );
});

it('returns a plain link unchanged as the attachment url', function () {
    Storage::fake('public');

    $visit = makeAttachmentWorkVisit();

    $this->actingAs($this->admin)
        ->post(route('work-visits.reports.store', $visit), [
            'visited_at' => now()->toDateString(),
            'location' => 'Kantor Klien',
            'attachment_path' => 'https://drive.example.com/file/123',
        ])
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->get(route('work-visits.show', $visit))
        ->assertInertia(fn (Assert $page) => $page
            ->where('workVisit.reports.0.attachment_url', 'https://drive.example.com/file/123'),
        );
});
