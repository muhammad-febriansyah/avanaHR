<?php

namespace App\Http\Requests\PayrollRun;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollRunRequest extends FormRequest
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
            'type' => ['required', Rule::in(['regular', 'thr', 'bonus', 'adjustment'])],
            'status' => ['required', Rule::in(['draft', 'calculated', 'approved', 'paid'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Tipe run wajib dipilih.',
            'status.required' => 'Status wajib dipilih.',
        ];
    }
}
