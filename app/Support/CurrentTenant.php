<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * Holds the tenant resolved for the current request/job lifecycle.
 * Registered as a singleton so models and scopes can read it anywhere.
 */
class CurrentTenant
{
    protected ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }

    public function forget(): void
    {
        $this->tenant = null;
    }
}
