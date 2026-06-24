<?php

namespace App\Http\Requests\PayrollComponent;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollComponentRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', Rule::unique('payroll_components', 'code')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['earning', 'deduction'])],
            'calc_type' => ['required', Rule::in(['fixed', 'percentage', 'formula'])],
            'is_taxable' => ['required', 'boolean'],
            'is_bpjs_base' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode komponen sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'type.required' => 'Tipe wajib dipilih.',
            'calc_type.required' => 'Metode hitung wajib dipilih.',
        ];
    }
}
