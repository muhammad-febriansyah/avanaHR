<?php

namespace App\Http\Requests\EmployeeBenefit;

use Illuminate\Foundation\Http\FormRequest;

class StoreBenefitClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payroll.approve');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'claim_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'claim_date.required' => 'Tanggal klaim wajib diisi.',
            'amount.required' => 'Nominal klaim wajib diisi.',
            'amount.min' => 'Nominal klaim minimal 1.',
            'description.required' => 'Keterangan wajib diisi.',
        ];
    }
}
