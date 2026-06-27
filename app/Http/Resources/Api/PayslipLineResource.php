<?php

namespace App\Http\Resources\Api;

use App\Models\PayslipLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PayslipLine
 */
class PayslipLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'component_code' => $this->component_code,
            'component_name' => $this->component_name,
            'type' => $this->type,
            'amount' => (int) $this->amount,
        ];
    }
}
