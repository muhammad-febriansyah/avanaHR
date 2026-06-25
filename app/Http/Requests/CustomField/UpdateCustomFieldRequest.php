<?php

namespace App\Http\Requests\CustomField;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomFieldRequest extends FormRequest
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
        return [
            'label' => ['required', 'string', 'max:255'],
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
            'label.required' => 'Label wajib diisi.',
            'type.required' => 'Tipe field wajib dipilih.',
        ];
    }
}
