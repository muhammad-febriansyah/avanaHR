<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Models\TenantProvision;
use App\Support\Features;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateTenantAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $tenant = Tenant::create(Arr::except($data, ['tier', 'features']));

            $tenant->subscriptions()->create([
                'tier' => $data['tier'],
                'status' => 'active',
                'starts_at' => now(),
                'feature_flags' => Features::flagsFrom($data['features'] ?? Features::keys()),
            ]);

            // Open a provisioning record so the platform can track onboarding.
            TenantProvision::create([
                'tenant_id' => $tenant->id,
                'status' => 'pending',
                'default_config_applied' => false,
                'created_by' => auth()->id(),
            ]);

            return $tenant;
        });
    }
}
