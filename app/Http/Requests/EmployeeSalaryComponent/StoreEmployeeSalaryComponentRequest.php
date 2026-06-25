<?php

namespace App\Http\Requests\EmployeeSalaryComponent;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeSalaryComponentRequest extends FormRequest
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
            'component_id' => ['required', 'exists:payroll_components,id'],
            'effective_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:0'],
            'rate' => ['nullable', 'numeric'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'component_id.required' => 'Komponen wajib dipilih.',
            'component_id.exists' => 'Komponen tidak valid.',
            'effective_date.required' => 'Tanggal berlaku wajib diisi.',
            'effective_date.date' => 'Tanggal berlaku tidak valid.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.integer' => 'Nominal harus berupa angka.',
            'amount.min' => 'Nominal tidak boleh kurang dari 0.',
            'rate.numeric' => 'Rate harus berupa angka.',
        ];
    }
}
