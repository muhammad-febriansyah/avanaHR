<?php

namespace App\Http\Requests\SalaryStructure;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryStructureRequest extends FormRequest
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
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'job_grade_id' => [
                'required', 'integer',
                Rule::exists('job_grades', 'id')->where('tenant_id', $tenantId),
                // One band per grade.
                Rule::unique('salary_structures', 'job_grade_id')->where('tenant_id', $tenantId),
            ],
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
            'job_grade_id.required' => 'Grade jabatan wajib dipilih.',
            'job_grade_id.unique' => 'Grade ini sudah punya struktur gaji.',
            'band_min.required' => 'Batas bawah wajib diisi.',
            'band_max.required' => 'Batas atas wajib diisi.',
            'band_max.gte' => 'Batas atas harus ≥ batas bawah.',
        ];
    }
}
