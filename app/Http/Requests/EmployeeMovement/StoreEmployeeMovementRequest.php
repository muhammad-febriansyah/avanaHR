<?php

namespace App\Http\Requests\EmployeeMovement;

use App\Enums\EmployeeMovementType;
use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreEmployeeMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'type' => ['required', new Enum(EmployeeMovementType::class)],
            'effective_date' => ['required', 'date'],
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')->where('tenant_id', $tenantId)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'job_grade_id' => ['nullable', 'integer', Rule::exists('job_grades', 'id')->where('tenant_id', $tenantId)],
            'manager_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'employment_type' => ['nullable', Rule::in(['permanent', 'contract', 'intern', 'outsource'])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'type.required' => 'Jenis movement wajib dipilih.',
            'effective_date.required' => 'Tanggal efektif wajib diisi.',
            'effective_date.date' => 'Tanggal efektif tidak valid.',
        ];
    }
}
