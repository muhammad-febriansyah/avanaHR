<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubmitWorkVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->employee !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'destination' => ['required', 'string', 'max:150'],
            'purpose' => ['required', 'string', 'max:500'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'transport_mode' => ['nullable', 'string', 'max:64'],
            'estimated_cost' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'destination.required' => 'Tujuan wajib diisi.',
            'purpose.required' => 'Keperluan wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}
