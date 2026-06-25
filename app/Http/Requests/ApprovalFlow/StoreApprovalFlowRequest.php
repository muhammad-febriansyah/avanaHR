<?php

namespace App\Http\Requests\ApprovalFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalFlowRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'transaction_type' => ['required', Rule::in([
                'leave', 'overtime', 'reimbursement', 'loan', 'attendance_correction', 'lifecycle', 'payroll',
            ])],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama alur wajib diisi.',
            'transaction_type.required' => 'Jenis transaksi wajib dipilih.',
        ];
    }
}
