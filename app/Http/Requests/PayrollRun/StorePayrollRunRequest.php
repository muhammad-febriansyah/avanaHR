<?php

namespace App\Http\Requests\PayrollRun;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollRunRequest extends FormRequest
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
            'period_id' => ['required', 'integer', Rule::exists('payroll_periods', 'id')->where('tenant_id', $tenantId)],
            'type' => ['required', Rule::in(['regular', 'thr', 'bonus', 'adjustment'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period_id.required' => 'Periode wajib dipilih.',
            'period_id.exists' => 'Periode tidak valid.',
            'type.required' => 'Tipe run wajib dipilih.',
        ];
    }
}
