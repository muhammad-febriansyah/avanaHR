<?php

namespace App\Http\Requests\Holiday;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends FormRequest
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
        $holiday = $this->route('holiday');

        return [
            'name' => ['required', 'string', 'max:150'],
            'date' => [
                'required', 'date',
                Rule::unique('holidays', 'date')
                    ->where('calendar_id', $holiday->calendar_id)
                    ->ignore($holiday->id),
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
        ];
    }
}
