<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('setting.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password_min_length' => ['required', 'integer', 'min:6', 'max:64'],
            'password_require_uppercase' => ['boolean'],
            'password_require_number' => ['boolean'],
            'password_require_symbol' => ['boolean'],
            'password_expiry_days' => ['required', 'integer', 'min:0', 'max:365'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'max_login_attempts' => ['required', 'integer', 'min:3', 'max:20'],
            'lockout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'enforce_2fa' => ['boolean'],
        ];
    }
}
