<?php

namespace App\Http\Requests\CostCenter;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCostCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employee.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('cost_centers', 'code')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode cost center sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
        ];
    }
}
