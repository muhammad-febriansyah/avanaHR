<?php

namespace App\Http\Requests\ShiftSwap;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftSwapRequest extends FormRequest
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
        $employee = Rule::exists('employees', 'id')->where('tenant_id', $tenantId);

        return [
            'requester_id' => ['required', 'integer', $employee],
            'target_id' => ['required', 'integer', 'different:requester_id', $employee],
            'date_a' => ['required', 'date'],
            'date_b' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'requester_id.required' => 'Karyawan pengaju wajib dipilih.',
            'target_id.required' => 'Karyawan tujuan wajib dipilih.',
            'target_id.different' => 'Karyawan tujuan harus berbeda dari pengaju.',
            'date_a.required' => 'Tanggal pengaju wajib diisi.',
            'date_b.required' => 'Tanggal tujuan wajib diisi.',
        ];
    }
}
