<?php

namespace App\Http\Requests\Schedule;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attendance.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'shift_id' => ['required', 'integer', Rule::exists('shifts', 'id')->where('tenant_id', $tenantId)],
            'date' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'shift_id.required' => 'Shift wajib dipilih.',
            'date.required' => 'Tanggal wajib diisi.',
        ];
    }
}
