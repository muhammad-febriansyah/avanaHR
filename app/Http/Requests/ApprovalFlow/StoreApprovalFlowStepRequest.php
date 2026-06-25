<?php

namespace App\Http\Requests\ApprovalFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalFlowStepRequest extends FormRequest
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
            'approver_type' => ['required', Rule::in(['manager', 'department_head', 'role', 'user'])],
            'approver_ref' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['all', 'any'])],
            'min_approvals' => ['required', 'integer', 'min:1', 'max:10'],
            'sla_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'allow_delegate' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'approver_type.required' => 'Tipe penyetuju wajib dipilih.',
            'min_approvals.required' => 'Minimal persetujuan wajib diisi.',
        ];
    }
}
