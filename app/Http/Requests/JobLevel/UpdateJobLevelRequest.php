<?php

namespace App\Http\Requests\JobLevel;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobLevelRequest extends FormRequest
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
        $jobLevelId = $this->route('job_level')->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('job_levels', 'code')->where('tenant_id', $tenantId)->ignore($jobLevelId)],
            'name' => ['required', 'string', 'max:150'],
            'order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode jenjang jabatan sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'order.integer' => 'Urutan harus berupa angka.',
        ];
    }
}
