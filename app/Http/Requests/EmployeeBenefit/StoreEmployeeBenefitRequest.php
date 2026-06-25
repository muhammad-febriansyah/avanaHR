<?php

namespace App\Http\Requests\EmployeeBenefit;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeBenefitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payroll.approve');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('tenant_id', $tenantId),
                Rule::unique('employee_benefits', 'employee_id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('benefit_type_id', $this->benefit_type_id)
                    ->where('period_year', $this->period_year)),
            ],
            'benefit_type_id' => ['required', 'integer', Rule::exists('benefit_types', 'id')->where('tenant_id', $tenantId)],
            'period_year' => ['required', 'integer', 'between:2000,2100'],
            'quota' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'employee_id.exists' => 'Karyawan tidak ditemukan.',
            'employee_id.unique' => 'Benefit ini sudah ditetapkan untuk karyawan tersebut pada tahun yang sama.',
            'benefit_type_id.required' => 'Jenis benefit wajib dipilih.',
            'benefit_type_id.exists' => 'Jenis benefit tidak ditemukan.',
            'period_year.required' => 'Tahun periode wajib diisi.',
            'period_year.between' => 'Tahun periode tidak valid.',
            'quota.required' => 'Plafon wajib diisi.',
            'quota.min' => 'Plafon tidak boleh kurang dari 0.',
        ];
    }
}
