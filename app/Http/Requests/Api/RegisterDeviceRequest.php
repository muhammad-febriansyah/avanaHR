<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:128'],
            'platform' => ['required', Rule::in(['android', 'ios'])],
            'fcm_token' => ['nullable', 'string', 'max:512'],
            'biometric_enabled' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'device_id.required' => 'Device ID wajib diisi.',
            'platform.in' => 'Platform harus android atau ios.',
        ];
    }
}
