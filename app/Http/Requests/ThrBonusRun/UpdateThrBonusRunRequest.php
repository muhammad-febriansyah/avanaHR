<?php

namespace App\Http\Requests\ThrBonusRun;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThrBonusRunRequest extends FormRequest
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
            'type' => ['required', Rule::in(['thr', 'bonus'])],
            'period_ref' => ['required', 'string', 'max:150'],
            'status' => ['required', Rule::in(['draft', 'calculated', 'approved', 'disbursed'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Tipe wajib dipilih.',
            'period_ref.required' => 'Periode/keterangan wajib diisi.',
            'status.required' => 'Status wajib dipilih.',
        ];
    }
}
