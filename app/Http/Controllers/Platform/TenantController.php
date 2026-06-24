<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Tenant\CreateTenantAction;
use App\Actions\Tenant\DeleteTenantAction;
use App\Actions\Tenant\UpdateTenantAction;
use App\Enums\SubscriptionTier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Models\Tenant;
use App\Repositories\Tenant\TenantRepository;
use App\Support\Features;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(private readonly TenantRepository $tenants) {}

    public function index(Request $request): Response
    {
        return Inertia::render('platform/tenants/index', [
            'tenants' => $this->tenants->paginateForIndex(
                $request->only('search', 'status', 'sort', 'dir', 'per_page'),
            ),
            'filters' => (object) $request->only('search', 'status', 'sort', 'dir', 'per_page'),
            'statuses' => $this->statusOptions(),
            'tiers' => $this->tierOptions(),
            'breadcrumbs' => $this->baseCrumbs(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/tenants/create', [
            'statuses' => $this->statusOptions(),
            'tiers' => $this->tierOptions(),
            'featureCatalog' => Features::catalog(),
            'enabledFeatures' => Features::keys(),
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => 'Tambah', 'href' => route('platform.tenants.create')]],
        ]);
    }

    public function store(StoreTenantRequest $request, CreateTenantAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant berhasil ditambahkan.']);

        return redirect()->route('platform.tenants.index');
    }

    public function show(Tenant $tenant): Response
    {
        return Inertia::render('platform/tenants/show', [
            'tenant' => $this->tenants->loadDetail($tenant),
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => $tenant->name, 'href' => route('platform.tenants.show', $tenant)]],
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('platform/tenants/edit', [
            'tenant' => $tenant->load('subscription'),
            'statuses' => $this->statusOptions(),
            'tiers' => $this->tierOptions(),
            'featureCatalog' => Features::catalog(),
            'enabledFeatures' => Features::enabledFrom($tenant->subscription?->feature_flags),
            'breadcrumbs' => [
                ...$this->baseCrumbs(),
                ['title' => $tenant->name, 'href' => route('platform.tenants.show', $tenant)],
                ['title' => 'Edit', 'href' => route('platform.tenants.edit', $tenant)],
            ],
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant, UpdateTenantAction $action): RedirectResponse
    {
        $action->handle($tenant, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant berhasil diperbarui.']);

        return redirect()->route('platform.tenants.index');
    }

    public function destroy(Tenant $tenant, DeleteTenantAction $action): RedirectResponse
    {
        $action->handle($tenant);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant berhasil dihapus.']);

        return redirect()->route('platform.tenants.index');
    }

    /**
     * @return list<array{title: string, href: string}>
     */
    private function baseCrumbs(): array
    {
        return [
            ['title' => 'Dashboard', 'href' => route('dashboard')],
            ['title' => 'Tenant', 'href' => route('platform.tenants.index')],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => 'active', 'label' => 'Aktif'],
            ['value' => 'suspended', 'label' => 'Ditangguhkan'],
            ['value' => 'inactive', 'label' => 'Nonaktif'],
        ];
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
