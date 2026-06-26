<?php

namespace App\Http\Requests\Department;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employee.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'head_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode departemen sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'parent_id.exists' => 'Departemen induk tidak valid.',
            'head_employee_id.exists' => 'Kepala departemen tidak valid.',
        ];
    }
}
