<?php

namespace App\Http\Requests\EmployeeDocument;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employee.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(['ktp', 'npwp', 'kk', 'ijazah', 'contract', 'bpjs', 'sim', 'passport', 'other'])],
            'number' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'reminder_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'access_level' => ['required', Rule::in(['public', 'internal', 'confidential'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_type.required' => 'Jenis dokumen wajib dipilih.',
            'access_level.required' => 'Level akses wajib dipilih.',
            'expired_at.after_or_equal' => 'Tanggal kedaluwarsa harus setelah tanggal terbit.',
        ];
    }
}
