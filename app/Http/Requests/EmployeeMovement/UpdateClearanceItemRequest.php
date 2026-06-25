<?php

namespace App\Http\Requests\EmployeeMovement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClearanceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['done', 'waived'])],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status item clearance wajib dipilih.',
            'status.in' => 'Status item clearance tidak valid.',
            'note.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}
