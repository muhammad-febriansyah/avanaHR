<?php

namespace App\Http\Requests\Reimbursement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReimbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payroll.run');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['medical', 'transport', 'communication', 'entertainment', 'other'])],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'settlement' => ['required', Rule::in(['payroll', 'cash', 'transfer'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Kategori wajib dipilih.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.min' => 'Nominal harus lebih dari nol.',
        ];
    }
}
