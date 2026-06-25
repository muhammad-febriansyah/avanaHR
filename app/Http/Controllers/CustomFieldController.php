<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomField\StoreCustomFieldRequest;
use App\Http\Requests\CustomField\UpdateCustomFieldRequest;
use App\Models\CustomField;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomFieldController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CustomField::class);

        $entity = $request->string('entity_type')->toString() ?: null;

        $fields = CustomField::query()
            ->when($entity, fn ($query, $value) => $query->where('entity_type', $value))
            ->orderBy('entity_type')
            ->orderBy('order')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CustomField $field): array => [
                'id' => $field->id,
                'entity_type' => $field->entity_type,
                'key' => $field->key,
                'label' => $field->label,
                'type' => $field->type,
                'options' => $field->options ?? [],
                'is_required' => $field->is_required,
                'order' => $field->order,
            ]);

        return Inertia::render('custom-fields/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Custom Field', 'href' => route('custom-fields.index')],
            ],
            'fields' => $fields,
            'filters' => (object) ['entity_type' => $entity],
            'entities' => $this->entityOptions(),
            'types' => $this->typeOptions(),
        ]);
    }

    public function store(StoreCustomFieldRequest $request): RedirectResponse
    {
        $data = $request->validated();

        CustomField::create([
            'entity_type' => $data['entity_type'],
            'key' => $data['key'],
            'label' => $data['label'],
            'type' => $data['type'],
            'options' => $data['type'] === 'select' ? array_values($data['options'] ?? []) : null,
            'is_required' => $request->boolean('is_required'),
            'order' => $data['order'] ?? 0,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Custom field berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdateCustomFieldRequest $request, CustomField $customField): RedirectResponse
    {
        $data = $request->validated();

        $customField->update([
            'label' => $data['label'],
            'type' => $data['type'],
            'options' => $data['type'] === 'select' ? array_values($data['options'] ?? []) : null,
            'is_required' => $request->boolean('is_required'),
            'order' => $data['order'] ?? 0,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Custom field berhasil diperbarui.']);

        return back();
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        $this->authorize('delete', $customField);

        $customField->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Custom field berhasil dihapus.']);

        return back();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function entityOptions(): array
    {
        return [
            ['value' => 'employee', 'label' => 'Karyawan'],
            ['value' => 'company', 'label' => 'Perusahaan'],
            ['value' => 'department', 'label' => 'Departemen'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return [
            ['value' => 'text', 'label' => 'Teks'],
            ['value' => 'textarea', 'label' => 'Teks Panjang'],
            ['value' => 'number', 'label' => 'Angka'],
            ['value' => 'date', 'label' => 'Tanggal'],
            ['value' => 'select', 'label' => 'Pilihan (Dropdown)'],
            ['value' => 'checkbox', 'label' => 'Ya/Tidak'],
        ];
    }
}
