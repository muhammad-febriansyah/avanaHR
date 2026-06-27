<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubmitBenefitClaimRequest extends FormRequest
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
            'claim_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'description' => ['required', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'claim_date.required' => 'Tanggal klaim wajib diisi.',
            'amount.required' => 'Nominal klaim wajib diisi.',
            'amount.min' => 'Nominal klaim minimal 1.',
            'description.required' => 'Keterangan wajib diisi.',
        ];
    }
}
