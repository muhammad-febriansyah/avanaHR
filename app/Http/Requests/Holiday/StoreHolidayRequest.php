<?php

namespace App\Http\Requests\Holiday;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employee.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'calendar_id' => ['required', 'integer', Rule::exists('work_calendars', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:150'],
            'date' => [
                'required', 'date',
                Rule::unique('holidays', 'date')->where('calendar_id', (int) $this->input('calendar_id')),
            ],
            'is_national' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'date.required' => 'Tanggal wajib diisi.',
            'date.unique' => 'Tanggal libur sudah ada di kalender ini.',
            'calendar_id.required' => 'Kalender wajib dipilih.',
            'calendar_id.exists' => 'Kalender tidak valid.',
        ];
    }
}
