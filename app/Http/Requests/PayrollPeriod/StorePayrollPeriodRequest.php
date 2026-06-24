<?php

namespace App\Http\Requests\PayrollPeriod;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollPeriodRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', Rule::unique('payroll_periods', 'code')->where('tenant_id', $tenantId)],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'cutoff_date' => ['nullable', 'date'],
            'pay_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode periode sudah digunakan.',
            'month.required' => 'Bulan wajib dipilih.',
            'year.required' => 'Tahun wajib diisi.',
        ];
    }
}
