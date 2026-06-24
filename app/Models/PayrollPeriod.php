<?php

namespace App\Models;

use App\Enums\PayrollPeriodStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => PayrollPeriodStatus::class,
            'cutoff_date' => 'date',
            'pay_date' => 'date',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class, 'period_id');
    }
}
