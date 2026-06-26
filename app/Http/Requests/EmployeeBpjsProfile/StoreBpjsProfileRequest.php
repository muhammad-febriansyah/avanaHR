<?php

namespace App\Http\Requests\EmployeeBpjsProfile;

use Illuminate\Foundation\Http\FormRequest;

class StoreBpjsProfileRequest extends FormRequest
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
            'bpjs_kesehatan_no' => ['nullable', 'string', 'max:32'],
            'bpjs_tk_no' => ['nullable', 'string', 'max:32'],
            'kesehatan_basis' => ['nullable', 'integer', 'min:0'],
            'tk_basis' => ['nullable', 'integer', 'min:0'],
            'kesehatan' => ['boolean'],
            'jht' => ['boolean'],
            'jkk' => ['boolean'],
            'jkm' => ['boolean'],
            'jp' => ['boolean'],
        ];
    }
}
