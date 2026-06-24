<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for the authenticated user and binds it for the request:
 * sets the CurrentTenant singleton (drives TenantScope) and the spatie team id
 * (drives role/permission checks). Must run after authentication.
 */
class SetCurrentTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->user()?->getAttributes()['tenant_id'] ?? null;
        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        // Always (re)bind — clearing any stale tenant left in the singleton by a
        // prior request on a reused worker (Octane/queue) so a tenant-less user
        // (e.g. platform super-admin) never inherits another tenant's context.
        app(CurrentTenant::class)->set($tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant?->id);

        return $next($request);
    }
}
