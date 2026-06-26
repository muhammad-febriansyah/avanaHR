<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalDelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approval.act') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to_user_id' => [
                'required',
                Rule::exists('users', 'id')->where('tenant_id', $this->user()->tenant_id),
                Rule::notIn([$this->user()->id]),
            ],
            'transaction_type' => ['nullable', 'string', 'max:64'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_user_id.not_in' => 'Tidak bisa mendelegasikan ke diri sendiri.',
            'ends_at.after_or_equal' => 'Tanggal selesai harus setelah tanggal mulai.',
        ];
    }
}
