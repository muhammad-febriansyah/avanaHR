<?php

namespace App\Http\Resources\Api;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employee
 */
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employment = $this->whenLoaded('currentEmployment');

        return [
            'id' => $this->id,
            'employee_no' => $this->employee_no,
            'full_name' => $this->fullName(),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'birth_date' => $this->birth_date?->toDateString(),
            'birth_place' => $this->birth_place,
            'religion' => $this->religion,
            'marital_status' => $this->marital_status,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'join_date' => $this->join_date?->toDateString(),
            'photo_url' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
            'employment' => $this->currentEmployment ? [
                'company' => $this->currentEmployment->company?->name,
                'branch' => $this->currentEmployment->branch?->name,
                'department' => $this->currentEmployment->department?->name,
                'position' => $this->currentEmployment->position?->name,
                'job_grade' => $this->currentEmployment->jobGrade?->name,
                'employment_type' => $this->currentEmployment->employment_type instanceof \BackedEnum
                    ? $this->currentEmployment->employment_type->value
                    : $this->currentEmployment->employment_type,
                'effective_date' => $this->currentEmployment->effective_date?->toDateString(),
            ] : null,
        ];
    }
}
