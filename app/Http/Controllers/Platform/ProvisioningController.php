<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantProvision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ProvisioningController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('search');

        $tenants = Tenant::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $provisions = $this->provisionsByTenant($tenants->getCollection()->pluck('id'));

        $tenants->through(function (Tenant $tenant) use ($provisions): array {
            $provision = $provisions[$tenant->id] ?? null;

            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $provision?->status ?? 'pending',
                'config_applied' => (bool) ($provision?->default_config_applied ?? false),
                'provisioned_at' => $provision?->updated_at?->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render('platform/provisioning/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Provisioning', 'href' => route('platform.provisioning.index')],
            ],
            'tenants' => $tenants,
            'filters' => (object) $filters,
        ]);
    }

    public function apply(Tenant $tenant): RedirectResponse
    {
        $provision = TenantProvision::query()->firstOrNew(['tenant_id' => $tenant->id]);

        if ($provision->default_config_applied) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Tenant ini sudah ter-provisioning.']);

            return back();
        }

        $provision->fill([
            'status' => 'completed',
            'default_config_applied' => true,
            'created_by' => $provision->created_by ?? auth()->id(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Konfigurasi default diterapkan ke {$tenant->name}."]);

        return back();
    }

    /**
     * @param  Collection<int, int>  $tenantIds
     * @return Collection<int, TenantProvision>
     */
    private function provisionsByTenant(Collection $tenantIds): Collection
    {
        return TenantProvision::query()
            ->whereIn('tenant_id', $tenantIds)
            ->get()
            ->keyBy('tenant_id');
    }
}
