<?php

namespace App\Http\Requests\EmployeeTaxProfile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxProfileRequest extends FormRequest
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
            'ptkp_status' => ['required', Rule::in(['TK/0', 'TK/1', 'TK/2', 'TK/3', 'K/0', 'K/1', 'K/2', 'K/3'])],
            'npwp' => ['nullable', 'string', 'max:32'],
            'tax_method' => ['required', Rule::in(['ter', 'gross', 'nett'])],
            'beginning_ytd' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
