<?php

namespace App\Http\Requests\LeaveBalance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveBalanceRequest extends FormRequest
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
            'entitled' => ['required', 'numeric', 'min:0', 'max:9999'],
            'used' => ['required', 'numeric', 'min:0', 'max:9999'],
            'pending' => ['required', 'numeric', 'min:0', 'max:9999'],
            'expired' => ['required', 'numeric', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entitled.required' => 'Hak cuti wajib diisi.',
        ];
    }
}
