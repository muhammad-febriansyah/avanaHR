<?php

namespace App\Http\Requests\LeaveType;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('setting.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('leave_types', 'code')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:150'],
            'is_paid' => ['required', 'boolean'],
            'max_days_year' => ['nullable', 'integer', 'min:0', 'max:365'],
            'allow_negative' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode jenis cuti sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'max_days_year.integer' => 'Maksimal hari harus berupa angka.',
            'max_days_year.max' => 'Maksimal hari tidak boleh lebih dari 365.',
        ];
    }
}
