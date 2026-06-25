<?php

namespace App\Http\Requests\EmployeeBenefit;

use App\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideBenefitClaimRequest extends FormRequest
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
        return [
            'status' => ['required', Rule::enum(RequestStatus::class)->only([
                RequestStatus::Approved,
                RequestStatus::Rejected,
            ])],
            'decision_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Keputusan wajib dipilih.',
        ];
    }
}
