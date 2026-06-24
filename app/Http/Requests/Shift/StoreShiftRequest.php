<?php

namespace App\Http\Requests\Shift;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', Rule::unique('shifts', 'code')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:150'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_min' => ['required', 'integer', 'min:0', 'max:1440'],
            'is_overnight' => ['required', 'boolean'],
            'late_tolerance_min' => ['required', 'integer', 'min:0', 'max:1440'],
            'grace_min' => ['required', 'integer', 'min:0', 'max:1440'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode shift sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_time.required' => 'Jam selesai wajib diisi.',
        ];
    }
}
