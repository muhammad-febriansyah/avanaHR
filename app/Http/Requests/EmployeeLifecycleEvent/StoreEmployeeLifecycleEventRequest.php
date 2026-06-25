<?php

namespace App\Http\Requests\EmployeeLifecycleEvent;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeLifecycleEventRequest extends FormRequest
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

        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'type' => ['required', Rule::in([
                'hire', 'promotion', 'transfer', 'demotion', 'contract_change', 'resign', 'terminate', 'reactivate',
            ])],
            'effective_date' => ['required', 'date'],
            'from_value' => ['nullable', 'string', 'max:255'],
            'to_value' => ['nullable', 'string', 'max:255'],
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
            'type.required' => 'Jenis peristiwa wajib dipilih.',
            'effective_date.required' => 'Tanggal efektif wajib diisi.',
        ];
    }
}
