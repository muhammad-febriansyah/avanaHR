<?php

namespace App\Http\Requests\HrTicket;

use Illuminate\Foundation\Http\FormRequest;

class ReplyHrTicketRequest extends FormRequest
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
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Balasan tidak boleh kosong.',
        ];
    }
}
