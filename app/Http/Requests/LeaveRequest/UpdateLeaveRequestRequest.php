<?php

namespace App\Http\Requests\LeaveRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateLeaveRequestRequest extends FormRequest
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
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function requestedDays(): float
    {
        $start = Carbon::parse($this->input('start_date'));
        $end = Carbon::parse($this->input('end_date'));

        return (float) ($start->diffInDays($end) + 1);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus pada atau setelah tanggal mulai.',
        ];
    }
}
