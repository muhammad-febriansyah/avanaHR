<?php

namespace Database\Factories;

use App\Enums\SubscriptionTier;
use App\Models\TenantSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantSubscription>
 */
class TenantSubscriptionFactory extends Factory
{
    protected $model = TenantSubscription::class;

    public function definition(): array
    {
        return [
            'tier' => SubscriptionTier::Enterprise,
            'feature_flags' => [],
            'starts_at' => now()->startOfYear(),
            'ends_at' => now()->endOfYear(),
            'status' => 'active',
        ];
    }
}
