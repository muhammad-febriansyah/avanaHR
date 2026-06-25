<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeBenefit extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'quota' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function benefitType(): BelongsTo
    {
        return $this->belongsTo(BenefitType::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(BenefitClaim::class);
    }

    /**
     * Total of approved claims against this allocation.
     */
    public function usedAmount(): int
    {
        return (int) $this->claims()
            ->where('status', RequestStatus::Approved->value)
            ->sum('amount');
    }

    /**
     * Remaining quota after approved claims (never below zero).
     */
    public function remainingQuota(): int
    {
        return max(0, $this->quota - $this->usedAmount());
    }
}
