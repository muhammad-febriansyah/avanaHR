<?php

namespace App\Http\Requests\CustomField;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('setting.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'entity_type' => ['required', Rule::in(['employee', 'company', 'department'])],
            'label' => ['required', 'string', 'max:255'],
            'key' => [
                'required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('custom_fields', 'key')
                    ->where('tenant_id', $tenantId)
                    ->where('entity_type', $this->input('entity_type')),
            ],
            'type' => ['required', Rule::in(['text', 'textarea', 'number', 'date', 'select', 'checkbox'])],
            'options' => ['array'],
            'options.*' => ['string', 'max:100'],
            'is_required' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entity_type.required' => 'Entitas wajib dipilih.',
            'label.required' => 'Label wajib diisi.',
            'key.required' => 'Kunci wajib diisi.',
            'key.regex' => 'Kunci harus huruf kecil, angka, atau underscore (diawali huruf).',
            'key.unique' => 'Kunci sudah dipakai untuk entitas ini.',
            'type.required' => 'Tipe field wajib dipilih.',
        ];
    }
}
