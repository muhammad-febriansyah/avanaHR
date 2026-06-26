<?php

namespace App\Http\Requests\SalaryStructure;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalaryStructureRequest extends FormRequest
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
            'band_min' => ['required', 'integer', 'min:0'],
            'band_max' => ['required', 'integer', 'gte:band_min'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'band_min.required' => 'Batas bawah wajib diisi.',
            'band_max.required' => 'Batas atas wajib diisi.',
            'band_max.gte' => 'Batas atas harus ≥ batas bawah.',
        ];
    }
}
