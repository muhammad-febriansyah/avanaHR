<?php

namespace App\Models\Scopes;

use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that constrains tenant-owned models to the current tenant.
 * No constraint is applied when no tenant is resolved (console, super-admin).
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = app(CurrentTenant::class);

        if ($tenant->check()) {
            $builder->where($model->getTable().'.tenant_id', $tenant->id());
        }
    }
}
