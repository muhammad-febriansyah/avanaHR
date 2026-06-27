<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActOnApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission is enforced in the controller, where the tenant team
        // context is reliably bound (see ApprovalController).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject', 'revise'])],
            // Reason is mandatory when rejecting or asking for a revision.
            'reason' => ['nullable', 'required_if:action,reject,revise', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Aksi wajib diisi.',
            'action.in' => 'Aksi harus approve, reject, atau revise.',
            'reason.required_if' => 'Alasan wajib diisi untuk menolak atau meminta revisi.',
        ];
    }
}
