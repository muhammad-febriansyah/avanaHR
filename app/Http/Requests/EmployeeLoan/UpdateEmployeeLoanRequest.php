<?php

namespace App\Http\Requests\EmployeeLoan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeLoanRequest extends FormRequest
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
            'principal' => ['required', 'integer', 'min:1', 'max:10000000000'],
            'tenor_months' => ['required', 'integer', 'min:1', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'principal.required' => 'Pokok pinjaman wajib diisi.',
            'principal.min' => 'Pokok pinjaman harus lebih dari nol.',
            'tenor_months.required' => 'Tenor wajib diisi.',
            'tenor_months.min' => 'Tenor minimal 1 bulan.',
        ];
    }
}
