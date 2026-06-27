<?php

namespace App\Http\Resources\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'avatar_url' => $this->avatar_url,
            'tenant_id' => $this->tenant_id,
            'roles' => $this->getRoleNames(),
            'employee' => $this->employee
                ? new ProfileResource($this->employee->loadMissing([
                    'currentEmployment.company',
                    'currentEmployment.branch',
                    'currentEmployment.department',
                    'currentEmployment.position',
                    'currentEmployment.jobGrade',
                ]))
                : null,
        ];
    }
}
