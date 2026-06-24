<?php

namespace App\Http\Requests\Reimbursement;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReimbursementRequest extends FormRequest
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
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
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
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'category.required' => 'Kategori wajib dipilih.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.min' => 'Nominal harus lebih dari nol.',
        ];
    }
}
