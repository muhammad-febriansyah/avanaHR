<?php

namespace App\Http\Requests\LeaveRequest;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leave.approve');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('tenant_id', $tenantId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Reject the request when it would overdraw a configured leave balance,
     * unless the leave type explicitly permits a negative balance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $days = $this->requestedDays();
            $year = (int) Carbon::parse($this->input('start_date'))->year;

            $balance = LeaveBalance::query()
                ->where('employee_id', $this->input('employee_id'))
                ->where('leave_type_id', $this->input('leave_type_id'))
                ->where('year', $year)
                ->first();

            if ($balance === null) {
                return;
            }

            $allowNegative = (bool) LeaveType::whereKey($this->input('leave_type_id'))->value('allow_negative');

            if (! $allowNegative && $days > (float) $balance->available) {
                $validator->errors()->add('end_date', "Saldo cuti tidak mencukupi (sisa {$balance->available} hari).");
            }
        });
    }

    public function requestedDays(): float
    {
        $start = Carbon::parse($this->input('start_date'));
        $end = Carbon::parse($this->input('end_date'));

        return (float) ($start->diffInDays($end) + 1);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'leave_type_id.required' => 'Jenis cuti wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus pada atau setelah tanggal mulai.',
        ];
    }
}
