<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollComponent\StorePayrollComponentRequest;
use App\Http\Requests\PayrollComponent\UpdatePayrollComponentRequest;
use App\Models\PayrollComponent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayrollComponentController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', PayrollComponent::class);

        $components = PayrollComponent::query()
            ->orderBy('type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'calc_type', 'is_taxable', 'is_bpjs_base'])
            ->map(fn (PayrollComponent $component): array => [
                'id' => $component->id,
                'code' => $component->code,
                'name' => $component->name,
                'type' => $component->type,
                'calc_type' => $component->calc_type,
                'is_taxable' => (bool) $component->is_taxable,
                'is_bpjs_base' => (bool) $component->is_bpjs_base,
            ]);

        return Inertia::render('payroll-components/index', [
            'components' => $components,
        ]);
    }

    public function store(StorePayrollComponentRequest $request): RedirectResponse
    {
        PayrollComponent::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komponen gaji berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdatePayrollComponentRequest $request, PayrollComponent $payrollComponent): RedirectResponse
    {
        $payrollComponent->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komponen gaji berhasil diperbarui.']);

        return back();
    }

    public function destroy(PayrollComponent $payrollComponent): RedirectResponse
    {
        $this->authorize('delete', $payrollComponent);

        if ($payrollComponent->salaryComponents()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Komponen masih dipakai pada struktur gaji karyawan.']);

            return back();
        }

        $payrollComponent->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komponen gaji berhasil dihapus.']);

        return back();
    }
}
