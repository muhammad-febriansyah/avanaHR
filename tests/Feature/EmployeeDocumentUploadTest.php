<?php

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Tenant;
use App\Models\User;
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
 * @return array<string, mixed>
 */
function uploadDocumentPayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'document_type' => 'ktp',
        'number' => '3174010101900099',
        'issued_at' => '2024-01-01',
        'expired_at' => '2029-01-01',
        'reminder_days' => 30,
        'access_level' => 'internal',
    ], $overrides);
}

it('stores an uploaded document under the tenant-scoped path and saves file_path', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('employee-documents.store'), uploadDocumentPayload([
            'file' => UploadedFile::fake()->create('ktp.pdf', 120, 'application/pdf'),
        ]))
        ->assertRedirect();

    $document = EmployeeDocument::where('number', '3174010101900099')->firstOrFail();

    expect($document->file_path)->not->toBeNull();
    expect($document->file_path)->toStartWith('documents/'.$this->tenant->id.'/');
    Storage::disk('public')->assertExists($document->file_path);
});

it('creates a document without a file (file is optional)', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('employee-documents.store'), uploadDocumentPayload())
        ->assertRedirect();

    $document = EmployeeDocument::where('number', '3174010101900099')->firstOrFail();

    expect($document->file_path)->toBeNull();
});

it('rejects a document upload with a disallowed mime type', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('employee-documents.store'), uploadDocumentPayload([
            'file' => UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'),
        ]))
        ->assertSessionHasErrors('file');

    expect(EmployeeDocument::where('number', '3174010101900099')->exists())->toBeFalse();
});

it('exposes a file_url for documents with a stored file', function () {
    Storage::fake('public');

    // Start clean so the uploaded document is the only row on the index.
    EmployeeDocument::query()->delete();

    $this->actingAs($this->admin)
        ->post(route('employee-documents.store'), uploadDocumentPayload([
            'file' => UploadedFile::fake()->image('ktp.png', 200, 120),
        ]))
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->get(route('employee-documents.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('documents.data.0.file_url', fn ($url) => is_string($url) && str_contains($url, 'documents/'.$this->tenant->id.'/')),
        );
});

it('deletes the stored file when the document is removed', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('employee-documents.store'), uploadDocumentPayload([
            'file' => UploadedFile::fake()->create('ktp.pdf', 80, 'application/pdf'),
        ]))
        ->assertRedirect();

    $document = EmployeeDocument::where('number', '3174010101900099')->firstOrFail();
    $path = $document->file_path;
    Storage::disk('public')->assertExists($path);

    $this->actingAs($this->admin)
        ->delete(route('employee-documents.destroy', $document))
        ->assertRedirect();

    Storage::disk('public')->assertMissing($path);
});
