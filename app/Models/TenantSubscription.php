<?php

namespace App\Models;

use App\Enums\SubscriptionTier;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantSubscription extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tier' => SubscriptionTier::class,
            'feature_flags' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
