<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubmitLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->employee !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'principal' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'tenor_months' => ['required', 'integer', 'min:1', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'principal.required' => 'Nominal pinjaman wajib diisi.',
            'principal.min' => 'Nominal harus lebih dari nol.',
            'tenor_months.required' => 'Tenor (bulan) wajib diisi.',
        ];
    }
}
