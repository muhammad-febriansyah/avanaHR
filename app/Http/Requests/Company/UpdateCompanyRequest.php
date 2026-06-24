<?php

namespace App\Http\Requests\Company;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
        $tenantId = app(CurrentTenant::class)->id();
        $companyId = $this->route('company')->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('companies', 'code')->where('tenant_id', $tenantId)->ignore($companyId)],
            'name' => ['required', 'string', 'max:150'],
            'npwp' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode perusahaan sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ];
    }
}
