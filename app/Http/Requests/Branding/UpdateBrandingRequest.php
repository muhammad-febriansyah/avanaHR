<?php

namespace App\Http\Requests\Branding;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('setting.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama perusahaan wajib diisi.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
        ];
    }
}
