<?php

namespace App\Http\Requests\AttendanceCorrection;

use App\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideAttendanceCorrectionRequest extends FormRequest
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
        return [
            'status' => ['required', Rule::enum(RequestStatus::class)->only([
                RequestStatus::Approved,
                RequestStatus::Rejected,
            ])],
        ];
    }
}
