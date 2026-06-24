<?php

namespace App\Http\Requests\Position;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
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
        $positionId = $this->route('position')->id;

        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('positions', 'code')->where('tenant_id', $tenantId)->ignore($positionId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'job_level_id' => ['nullable', 'integer', Rule::exists('job_levels', 'id')->where('tenant_id', $tenantId)],
            'job_grade_id' => ['nullable', 'integer', Rule::exists('job_grades', 'id')->where('tenant_id', $tenantId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode posisi sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'department_id.required' => 'Departemen wajib dipilih.',
            'department_id.exists' => 'Departemen tidak valid.',
        ];
    }
}
