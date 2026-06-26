<?php

namespace App\Http\Requests\ShiftPattern;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftPatternRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['cyclic'])],
            // Repeating cycle of shift ids; null entry = day off.
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['nullable', 'integer', Rule::exists('shifts', 'id')->where('tenant_id', $tenantId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pola wajib diisi.',
            'days.required' => 'Pola minimal satu hari.',
            'days.min' => 'Pola minimal satu hari.',
        ];
    }
}
