<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ESS self-service profile edit. Only contact fields are editable; the changes
 * flow through the maker-checker approval engine (sensitive data).
 */
class UpdateProfileRequest extends FormRequest
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
        $tenantId = $this->user()->tenant_id;
        $employeeId = $this->user()->employee_id;

        return [
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:300'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('employees', 'email')->where('tenant_id', $tenantId)->ignore($employeeId),
            ],
            'marital_status' => ['nullable', 'string', 'max:32'],
            'religion' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
        ];
    }
}
