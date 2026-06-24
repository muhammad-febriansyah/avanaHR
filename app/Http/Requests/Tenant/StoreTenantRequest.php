<?php

namespace App\Http\Requests\Tenant;

use App\Enums\SubscriptionTier;
use App\Support\Features;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already guarded by the EnsureSuperAdmin middleware.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'locale' => ['nullable', 'string', 'max:8'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'max:8'],
            'status' => ['required', Rule::in(['active', 'suspended', 'inactive'])],
            'tier' => ['required', Rule::enum(SubscriptionTier::class)],
            'features' => ['nullable', 'array'],
            'features.*' => [Rule::in(Features::keys())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama tenant wajib diisi.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug sudah digunakan.',
            'slug.alpha_dash' => 'Slug hanya boleh huruf, angka, dan tanda hubung.',
            'status.required' => 'Status wajib dipilih.',
            'tier.required' => 'Paket langganan wajib dipilih.',
        ];
    }
}
