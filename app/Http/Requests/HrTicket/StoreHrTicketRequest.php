<?php

namespace App\Http\Requests\HrTicket;

use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHrTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employee.view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['leave', 'payroll', 'attendance', 'data', 'general'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'Subjek wajib diisi.',
            'category.required' => 'Kategori wajib dipilih.',
            'priority.required' => 'Prioritas wajib dipilih.',
            'body.required' => 'Deskripsi tiket wajib diisi.',
        ];
    }
}
