<?php

namespace App\Http\Requests\Department;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
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
        $departmentId = $this->route('department')->id;

        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('departments', 'code')->where('tenant_id', $tenantId)->ignore($departmentId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('departments', 'id')->where('tenant_id', $tenantId),
                function (string $attribute, mixed $value, callable $fail) use ($departmentId): void {
                    if ((int) $value === (int) $departmentId) {
                        $fail('Departemen tidak boleh menjadi induk dirinya sendiri.');
                    }
                },
            ],
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
        ];
    }
}
