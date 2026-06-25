<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenefitClaim extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'claim_date' => 'date',
            'amount' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function employeeBenefit(): BelongsTo
    {
        return $this->belongsTo(EmployeeBenefit::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === RequestStatus::Pending;
    }
}
