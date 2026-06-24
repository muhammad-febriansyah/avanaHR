<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Support\Features;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateTenantAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data): Tenant {
            $tenant->update(Arr::except($data, ['tier', 'features']));

            $flags = Features::flagsFrom($data['features'] ?? Features::keys());
            $subscription = $tenant->subscription;

            if ($subscription) {
                $subscription->update([
                    'tier' => $data['tier'],
                    'feature_flags' => $flags,
                ]);
            } else {
                $tenant->subscriptions()->create([
                    'tier' => $data['tier'],
                    'status' => 'active',
                    'starts_at' => now(),
                    'feature_flags' => $flags,
                ]);
            }

            return $tenant;
        });
    }
}
