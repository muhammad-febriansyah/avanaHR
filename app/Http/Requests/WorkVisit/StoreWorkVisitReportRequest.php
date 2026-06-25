<?php

namespace App\Http\Requests\WorkVisit;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkVisitReportRequest extends FormRequest
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
        return [
            'visited_at' => ['required', 'date'],
            'location' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachment_path' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'visited_at.required' => 'Tanggal kunjungan wajib diisi.',
            'location.required' => 'Lokasi wajib diisi.',
        ];
    }
}
