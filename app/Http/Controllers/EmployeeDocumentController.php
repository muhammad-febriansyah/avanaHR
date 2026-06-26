<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeDocument\StoreEmployeeDocumentRequest;
use App\Http\Requests\EmployeeDocument\UpdateEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Support\CurrentTenant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeDocumentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $filters = $request->only('search', 'document_type', 'status');
        $today = Carbon::today();

        $documents = EmployeeDocument::query()
            ->with('employee:id,first_name,last_name,employee_no')
            ->when($filters['document_type'] ?? null, fn ($query, $type) => $query->where('document_type', $type))
            ->when($filters['status'] ?? null, function ($query, $status) use ($today) {
                match ($status) {
                    'expired' => $query->whereNotNull('expired_at')->whereDate('expired_at', '<', $today),
                    'expiring' => $query->whereNotNull('expired_at')
                        ->whereDate('expired_at', '>=', $today)
                        ->whereDate('expired_at', '<=', $today->copy()->addDays(30)),
                    'valid' => $query->where(fn ($q) => $q->whereNull('expired_at')
                        ->orWhereDate('expired_at', '>', $today->copy()->addDays(30))),
                    default => null,
                };
            })
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('number', 'like', "%{$search}%")
                ->orWhereHas('employee', fn ($e) => $e->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"))))
            ->orderByRaw('expired_at IS NULL')
            ->orderBy('expired_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmployeeDocument $document): array => [
                'id' => $document->id,
                'employee_name' => $document->employee?->fullName(),
                'employee_no' => $document->employee?->employee_no,
                'document_type' => $document->document_type,
                'number' => $document->number,
                'issued_at' => $document->issued_at?->format('Y-m-d'),
                'expired_at' => $document->expired_at?->format('Y-m-d'),
                'access_level' => $document->access_level,
                'expiry_status' => $this->expiryStatus($document, $today),
                'file_url' => $document->file_path ? Storage::url($document->file_path) : null,
            ]);

        return Inertia::render('employee-documents/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Dokumen', 'href' => route('employee-documents.index')],
            ],
            'documents' => $documents,
            'filters' => (object) $filters,
            'types' => $this->typeOptions(),
            'accessLevels' => $this->accessOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EmployeeDocument::class);

        return Inertia::render('employee-documents/create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Dokumen', 'href' => route('employee-documents.index')],
                ['title' => 'Tambah', 'href' => route('employee-documents.create')],
            ],
            'employees' => $this->employeeOptions(),
            'types' => $this->typeOptions(),
            'accessLevels' => $this->accessOptions(),
        ]);
    }

    public function store(StoreEmployeeDocumentRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('file');

        if ($request->hasFile('file')) {
            // Per-tenant prefix keeps uploaded documents isolated on disk.
            $tenantId = app(CurrentTenant::class)->id();
            $data['file_path'] = $request->file('file')->store('documents/'.$tenantId, 'public');
        }

        EmployeeDocument::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen berhasil ditambahkan.']);

        return redirect()->route('employee-documents.index');
    }

    public function edit(EmployeeDocument $employeeDocument): Response
    {
        $this->authorize('update', $employeeDocument);

        $employeeDocument->loadMissing('employee:id,first_name,last_name,employee_no');

        return Inertia::render('employee-documents/edit', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Dokumen', 'href' => route('employee-documents.index')],
                ['title' => 'Edit', 'href' => route('employee-documents.edit', $employeeDocument)],
            ],
            'document' => [
                'id' => $employeeDocument->id,
                'employee_id' => $employeeDocument->employee_id,
                'employee_name' => $employeeDocument->employee?->fullName(),
                'employee_no' => $employeeDocument->employee?->employee_no,
                'document_type' => $employeeDocument->document_type,
                'number' => $employeeDocument->number,
                'issued_at' => $employeeDocument->issued_at?->format('Y-m-d'),
                'expired_at' => $employeeDocument->expired_at?->format('Y-m-d'),
                'reminder_days' => $employeeDocument->reminder_days,
                'access_level' => $employeeDocument->access_level,
                'file_url' => $employeeDocument->file_path ? Storage::url($employeeDocument->file_path) : null,
            ],
            'types' => $this->typeOptions(),
            'accessLevels' => $this->accessOptions(),
        ]);
    }

    public function update(UpdateEmployeeDocumentRequest $request, EmployeeDocument $employeeDocument): RedirectResponse
    {
        $employeeDocument->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen berhasil diperbarui.']);

        return redirect()->route('employee-documents.index');
    }

    public function destroy(EmployeeDocument $employeeDocument): RedirectResponse
    {
        $this->authorize('delete', $employeeDocument);

        if ($employeeDocument->file_path) {
            Storage::disk('public')->delete($employeeDocument->file_path);
        }

        $employeeDocument->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen berhasil dihapus.']);

        return back();
    }

    private function expiryStatus(EmployeeDocument $document, Carbon $today): string
    {
        if (! $document->expired_at) {
            return 'none';
        }

        if ($document->expired_at->lt($today)) {
            return 'expired';
        }

        if ($document->expired_at->lte($today->copy()->addDays(30))) {
            return 'expiring';
        }

        return 'valid';
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function employeeOptions(): Collection
    {
        return Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'label' => $employee->fullName().' ('.$employee->employee_no.')',
            ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return [
            ['value' => 'ktp', 'label' => 'KTP'],
            ['value' => 'npwp', 'label' => 'NPWP'],
            ['value' => 'kk', 'label' => 'Kartu Keluarga'],
            ['value' => 'ijazah', 'label' => 'Ijazah'],
            ['value' => 'contract', 'label' => 'Kontrak Kerja'],
            ['value' => 'bpjs', 'label' => 'BPJS'],
            ['value' => 'sim', 'label' => 'SIM'],
            ['value' => 'passport', 'label' => 'Paspor'],
            ['value' => 'other', 'label' => 'Lainnya'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function accessOptions(): array
    {
        return [
            ['value' => 'public', 'label' => 'Publik'],
            ['value' => 'internal', 'label' => 'Internal'],
            ['value' => 'confidential', 'label' => 'Rahasia'],
        ];
    }
}
