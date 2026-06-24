<?php

namespace App\Http\Requests\BankFile;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankFileRequest extends FormRequest
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
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'run_id' => ['required', 'integer', Rule::exists('payroll_runs', 'id')->where('tenant_id', $tenantId)],
            'bank_code' => ['required', Rule::in(['BCA', 'BNI', 'BRI', 'Mandiri', 'CIMB', 'Permata'])],
            'format' => ['required', Rule::in(['csv', 'txt'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'run_id.required' => 'Proses payroll wajib dipilih.',
            'run_id.exists' => 'Proses payroll tidak valid.',
            'bank_code.required' => 'Bank wajib dipilih.',
            'format.required' => 'Format wajib dipilih.',
        ];
    }
}
