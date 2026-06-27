<?php

namespace App\Http\Resources\Api;

use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payslip
 */
class PayslipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period' => $this->run?->period?->code,
            'period_month' => $this->run?->period?->month,
            'period_year' => $this->run?->period?->year,
            'run_no' => $this->run?->run_no,
            'gross' => (int) $this->gross,
            'deductions' => (int) $this->deductions,
            'tax' => (int) $this->tax,
            'bpjs_employee' => (int) $this->bpjs_employee,
            'net' => (int) $this->net,
            'issued_at' => $this->created_at?->toDateString(),
            'lines' => PayslipLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
