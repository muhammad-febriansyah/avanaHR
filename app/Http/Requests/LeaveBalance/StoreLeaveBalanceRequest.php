<?php

namespace App\Http\Requests\LeaveBalance;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leave.approve');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'employee_id' => [
                'required', 'integer',
                Rule::exists('employees', 'id')->where('tenant_id', $tenantId),
                Rule::unique('leave_balances', 'employee_id')
                    ->where('tenant_id', $tenantId)
                    ->where('leave_type_id', (int) $this->input('leave_type_id'))
                    ->where('year', (int) $this->input('year')),
            ],
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('tenant_id', $tenantId)],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'entitled' => ['required', 'numeric', 'min:0', 'max:9999'],
            'used' => ['required', 'numeric', 'min:0', 'max:9999'],
            'pending' => ['required', 'numeric', 'min:0', 'max:9999'],
            'expired' => ['required', 'numeric', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'employee_id.unique' => 'Saldo untuk karyawan, jenis cuti, dan tahun ini sudah ada.',
            'leave_type_id.required' => 'Jenis cuti wajib dipilih.',
            'year.required' => 'Tahun wajib diisi.',
            'entitled.required' => 'Hak cuti wajib diisi.',
        ];
    }
}
