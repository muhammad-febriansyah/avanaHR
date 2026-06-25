<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantProvision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantProvision>
 */
class TenantProvisionFactory extends Factory
{
    protected $model = TenantProvision::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'status' => 'pending',
            'default_config_applied' => false,
            'admin_user_id' => null,
            'created_by' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
            'default_config_applied' => true,
        ]);
    }
}
