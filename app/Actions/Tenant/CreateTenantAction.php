<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
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

            return $tenant;
        });
    }
}
