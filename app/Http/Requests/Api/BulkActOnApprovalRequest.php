<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkActOnApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission is enforced in the controller (tenant team context).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'required_if:action,reject', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Pilih minimal satu pengajuan.',
            'action.in' => 'Aksi massal harus approve atau reject.',
            'reason.required_if' => 'Alasan wajib diisi untuk menolak.',
        ];
    }
}
