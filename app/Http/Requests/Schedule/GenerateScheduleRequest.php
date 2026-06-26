<?php

namespace App\Http\Requests\Schedule;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateScheduleRequest extends FormRequest
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
            'pattern_id' => ['required', 'integer', Rule::exists('shift_patterns', 'id')->where('tenant_id', $tenantId)],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pattern_id.required' => 'Pola shift wajib dipilih.',
            'employee_ids.required' => 'Pilih minimal satu karyawan.',
            'employee_ids.min' => 'Pilih minimal satu karyawan.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah tanggal mulai.',
        ];
    }
}
