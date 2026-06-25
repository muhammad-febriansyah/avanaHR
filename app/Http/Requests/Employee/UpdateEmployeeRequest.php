<?php

namespace App\Http\Requests\Employee;

use App\Enums\EmployeeStatus;
use App\Http\Requests\Concerns\ValidatesCustomFields;
use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    use ValidatesCustomFields;

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
        $employeeId = $this->route('employee')->id;

        return [
            ...$this->customFieldRules('employee'),
            'employee_no' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_no')->where('tenant_id', $tenantId)->ignore($employeeId)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:32'],
            'marital_status' => ['nullable', 'string', 'max:32'],
            'nik_ktp' => ['nullable', 'string', 'max:32', Rule::unique('employees', 'nik_ktp')->where('tenant_id', $tenantId)->ignore($employeeId)],
            'npwp' => ['nullable', 'string', 'max:32', Rule::unique('employees', 'npwp')->where('tenant_id', $tenantId)->ignore($employeeId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->where('tenant_id', $tenantId)->ignore($employeeId)],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'join_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->customFieldMessages('employee'),
            'employee_no.required' => 'Nomor karyawan wajib diisi.',
            'employee_no.unique' => 'Nomor karyawan sudah digunakan.',
            'first_name.required' => 'Nama depan wajib diisi.',
            'nik_ktp.unique' => 'NIK KTP sudah terdaftar.',
            'npwp.unique' => 'NPWP sudah terdaftar.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'status.required' => 'Status wajib dipilih.',
            'birth_date.date' => 'Tanggal lahir tidak valid.',
            'join_date.date' => 'Tanggal bergabung tidak valid.',
        ];
    }
}
