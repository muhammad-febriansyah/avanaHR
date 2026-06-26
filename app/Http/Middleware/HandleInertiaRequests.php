<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Notification;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Features;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'org' => $this->resolveBranding($request->user()),
            'notifications' => $this->resolveNotifications($request->user()),
            'impersonating' => $request->session()->has('impersonator_id')
                ? ['name' => $request->user()?->name]
                : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * The current user's recent in-app notifications + unread count for the
     * topbar bell. Tenant-scoped via the model's global scope.
     *
     * @return array{unread: int, items: array<int, array<string, mixed>>}
     */
    private function resolveNotifications(?User $user): array
    {
        if ($user === null || ($user->getAttributes()['tenant_id'] ?? null) === null) {
            return ['unread' => 0, 'items' => []];
        }

        $items = Notification::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(10)
            ->get(['id', 'type', 'payload', 'read_at', 'created_at']);

        return [
            'unread' => Notification::query()->where('user_id', $user->id)->whereNull('read_at')->count(),
            'items' => $items->map(fn (Notification $n): array => [
                'id' => $n->id,
                'title' => $n->payload['title'] ?? $this->notificationTitle($n->type),
                'read' => $n->read_at !== null,
                'at' => $n->created_at?->diffForHumans(),
            ])->all(),
        ];
    }

    private function notificationTitle(string $type): string
    {
        return match ($type) {
            'approval.assigned' => 'Pengajuan menunggu persetujuan Anda',
            'approval.approved' => 'Pengajuan Anda disetujui',
            'approval.rejected' => 'Pengajuan Anda ditolak',
            'approval.revision' => 'Pengajuan Anda perlu revisi',
            'approval.sla_reminder' => 'Persetujuan melewati SLA',
            'approval.escalated' => 'Persetujuan dieskalasi ke Anda',
            'document.expiring' => 'Dokumen akan kedaluwarsa',
            'contract.expiring' => 'Kontrak akan berakhir',
            default => 'Notifikasi',
        };
    }

    /**
     * Tenant branding (company name + logo) for the current user's tenant.
     * Strictly scoped to the user's own tenant — never another tenant's data.
     *
     * @return array{name: string, logo: string|null}
     */
    private function resolveBranding(?User $user): array
    {
        $tenantId = $user?->getAttributes()['tenant_id'] ?? null;

        if (! $tenantId) {
            return ['name' => config('app.name'), 'logo' => null];
        }

        $company = Company::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->first(['name', 'logo_path']);

        return [
            'name' => $company?->name ?? config('app.name'),
            'logo' => $company?->logo_path ? Storage::url($company->logo_path) : null,
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
