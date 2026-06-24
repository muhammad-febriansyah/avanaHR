<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Features;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $this->resolvePermissions($request->user()),
            ],
            'features' => $this->resolveFeatures($request->user()),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Resolve the enabled tenant feature keys for the user (super-admin = all).
     *
     * @return list<string>
     */
    protected function resolveFeatures(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->getAttributes()['is_super_admin'] ?? false) {
            return Features::keys();
        }

        $tenantId = $user->getAttributes()['tenant_id'] ?? null;

        if (! $tenantId) {
            return [];
        }

        $tenant = Tenant::with('subscription')->find($tenantId);

        return Features::enabledFrom($tenant?->subscription?->feature_flags);
    }

    /**
     * Resolve the authenticated user's permission names within their tenant team.
     *
     * @return list<string>
     */
    protected function resolvePermissions(?User $user): array
    {
        if (! $user) {
            return [];
        }

        // Super-admin sees every menu/permission across the platform.
        if ($user->getAttributes()['is_super_admin'] ?? false) {
            return Permission::query()->pluck('name')->values()->all();
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->getAttributes()['tenant_id'] ?? null);

        return $user->getAllPermissions()->pluck('name')->values()->all();
    }
}
