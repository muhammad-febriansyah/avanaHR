<?php

namespace App\Http\Requests\EmployeeLoan;

use App\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideEmployeeLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payroll.run');
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
        ];
    }
}
