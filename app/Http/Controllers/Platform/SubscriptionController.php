<?php

namespace App\Http\Controllers\Platform;

use App\Enums\SubscriptionTier;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\Features;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'tier');
        $featureTotal = count(Features::keys());

        $tenants = Tenant::query()
            ->with('subscription')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"))
            ->when($filters['tier'] ?? null, fn ($query, $tier) => $query->whereHas(
                'subscription',
                fn ($q) => $q->where('tier', $tier),
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $tenants->through(fn (Tenant $tenant): array => [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'tier' => $tenant->subscription?->tier?->value,
            'status' => $tenant->subscription?->status,
            'features_enabled' => count(Features::enabledFrom($tenant->subscription?->feature_flags)),
            'features_total' => $featureTotal,
            'starts_at' => $tenant->subscription?->starts_at?->format('Y-m-d'),
            'ends_at' => $tenant->subscription?->ends_at?->format('Y-m-d'),
        ]);

        return Inertia::render('platform/subscriptions/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Langganan & Paket', 'href' => route('platform.subscriptions.index')],
            ],
            'tenants' => $tenants,
            'filters' => (object) $filters,
            'tiers' => $this->tierOptions(),
            'summary' => $this->summary(),
        ]);
    }

    /**
     * Change a tenant's subscription tier (paket).
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'tier' => ['required', Rule::enum(SubscriptionTier::class)],
        ]);

        $subscription = $tenant->subscription;

        if ($subscription !== null) {
            $subscription->update(['tier' => $data['tier'], 'status' => 'active']);
        } else {
            $tenant->subscription()->create(['tier' => $data['tier'], 'status' => 'active']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Paket {$tenant->name} diubah ke ".ucfirst($data['tier']).'.']);

        return back();
    }

    /**
     * Active-subscription counts per tier, for the overview cards.
     *
     * @return array<string, int>
     */
    private function summary(): array
    {
        $counts = TenantSubscription::query()
            ->selectRaw('tier, COUNT(*) as total')
            ->groupBy('tier')
            ->pluck('total', 'tier');

        return collect(SubscriptionTier::cases())
            ->mapWithKeys(fn (SubscriptionTier $tier): array => [
                $tier->value => (int) ($counts[$tier->value] ?? 0),
            ])
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function tierOptions(): array
    {
        return array_map(fn (SubscriptionTier $tier): array => [
            'value' => $tier->value,
            'label' => ucfirst($tier->value),
        ], SubscriptionTier::cases());
    }
}
