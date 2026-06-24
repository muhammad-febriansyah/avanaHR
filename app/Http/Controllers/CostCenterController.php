<?php

namespace App\Http\Controllers;

use App\Http\Requests\CostCenter\StoreCostCenterRequest;
use App\Http\Requests\CostCenter\UpdateCostCenterRequest;
use App\Models\CostCenter;
use App\Models\EmployeeEmployment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CostCenterController extends Controller
{
    use AuthorizesRequests;

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function baseCrumbs(): array
    {
        return [
            ['title' => 'Dashboard', 'href' => route('dashboard')],
            ['title' => 'Cost Center', 'href' => route('cost-centers.index')],
        ];
    }

    public function index(): Response
    {
        $this->authorize('viewAny', CostCenter::class);

        $costCenters = CostCenter::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (CostCenter $costCenter): array => [
                'id' => $costCenter->id,
                'code' => $costCenter->code,
                'name' => $costCenter->name,
            ]);

        return Inertia::render('cost-centers/index', [
            'costCenters' => $costCenters,
            'breadcrumbs' => $this->baseCrumbs(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CostCenter::class);

        return Inertia::render('cost-centers/create', [
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => 'Tambah', 'href' => route('cost-centers.create')]],
        ]);
    }

    public function store(StoreCostCenterRequest $request): RedirectResponse
    {
        CostCenter::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cost center berhasil ditambahkan.']);

        return to_route('cost-centers.index');
    }

    public function edit(CostCenter $costCenter): Response
    {
        $this->authorize('update', $costCenter);

        return Inertia::render('cost-centers/edit', [
            'costCenter' => $costCenter->only(['id', 'code', 'name']),
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => $costCenter->name, 'href' => route('cost-centers.edit', $costCenter)]],
        ]);
    }

    public function update(UpdateCostCenterRequest $request, CostCenter $costCenter): RedirectResponse
    {
        $costCenter->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cost center berhasil diperbarui.']);

        return to_route('cost-centers.index');
    }

    public function destroy(CostCenter $costCenter): RedirectResponse
    {
        $this->authorize('delete', $costCenter);

        $inUse = EmployeeEmployment::query()
            ->where('cost_center_id', $costCenter->id)
            ->exists();

        if ($inUse) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Cost center masih dipakai data penempatan karyawan.']);

            return back();
        }

        $costCenter->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cost center berhasil dihapus.']);

        return back();
    }
}
