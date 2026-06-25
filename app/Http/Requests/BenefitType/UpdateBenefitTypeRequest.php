<?php

namespace App\Http\Requests\BenefitType;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBenefitTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payroll.approve');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('benefit_types', 'code')
                ->where('tenant_id', $tenantId)
                ->ignore($this->route('benefit_type')->id)],
            'name' => ['required', 'string', 'max:150'],
            'default_quota' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode jenis benefit sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'default_quota.integer' => 'Plafon default harus berupa angka.',
            'default_quota.min' => 'Plafon default tidak boleh kurang dari 0.',
        ];
    }
}
