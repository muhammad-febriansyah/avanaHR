<?php

namespace App\Http\Requests\JobGrade;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employee.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();
        $jobGradeId = $this->route('job_grade')->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('job_grades', 'code')->where('tenant_id', $tenantId)->ignore($jobGradeId)],
            'name' => ['required', 'string', 'max:150'],
            'salary_band_min' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'salary_band_max' => ['required', 'integer', 'min:0', 'max:1000000000000', 'gte:salary_band_min'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode grade jabatan sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'salary_band_min.required' => 'Batas bawah gaji wajib diisi.',
            'salary_band_max.required' => 'Batas atas gaji wajib diisi.',
            'salary_band_max.gte' => 'Batas atas gaji harus lebih besar atau sama dengan batas bawah.',
        ];
    }
}
