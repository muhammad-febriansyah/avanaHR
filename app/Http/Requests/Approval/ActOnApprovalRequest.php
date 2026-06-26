<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActOnApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approval.act') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject', 'revise'])],
            'reason' => ['nullable', 'string', 'max:1000', Rule::requiredIf(
                in_array($this->input('action'), ['reject', 'revise'], true),
            )],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan wajib diisi untuk penolakan atau permintaan revisi.',
        ];
    }
}
