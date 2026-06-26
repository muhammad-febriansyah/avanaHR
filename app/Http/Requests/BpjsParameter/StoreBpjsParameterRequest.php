<?php

namespace App\Http\Requests\BpjsParameter;

use Illuminate\Foundation\Http\FormRequest;

class StoreBpjsParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payroll.run') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'effective_date' => ['required', 'date'],
            'kes_rate_employee' => ['required', 'numeric', 'min:0', 'max:100'],
            'kes_rate_employer' => ['required', 'numeric', 'min:0', 'max:100'],
            'kes_cap' => ['required', 'integer', 'min:0'],
            'jht_employee' => ['required', 'numeric', 'min:0', 'max:100'],
            'jht_employer' => ['required', 'numeric', 'min:0', 'max:100'],
            'jkk' => ['required', 'numeric', 'min:0', 'max:100'],
            'jkm' => ['required', 'numeric', 'min:0', 'max:100'],
            'jp_employee' => ['required', 'numeric', 'min:0', 'max:100'],
            'jp_employer' => ['required', 'numeric', 'min:0', 'max:100'],
            'jp_cap' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'effective_date.required' => 'Tanggal berlaku wajib diisi.',
        ];
    }
}
