<?php

namespace App\Http\Requests\Role;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        $roleId = $this->route('role')->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('roles', 'name')->where(fn ($query) => $query->where('team_id', $tenantId))->ignore($roleId),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama role wajib diisi.',
            'name.unique' => 'Nama role sudah digunakan.',
            'permissions.*.exists' => 'Permission tidak valid.',
        ];
    }
}
