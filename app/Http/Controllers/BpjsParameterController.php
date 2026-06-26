<?php

namespace App\Http\Controllers;

use App\Http\Requests\BpjsParameter\StoreBpjsParameterRequest;
use App\Models\BpjsParameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tenant BPJS parameters (rates + caps), effective-dated. The payroll engine
 * picks the version effective for each run period.
 */
class BpjsParameterController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('payroll.view'), 403);

        $parameters = BpjsParameter::query()
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (BpjsParameter $param): array => [
                'id' => $param->id,
                'effective_date' => $param->effective_date?->format('Y-m-d'),
                'kes_rate_employee' => (float) $param->kes_rate_employee,
                'kes_rate_employer' => (float) $param->kes_rate_employer,
                'kes_cap' => (int) $param->kes_cap,
                'tk_rates' => $param->tk_rates ?? [],
            ]);

        return Inertia::render('bpjs-parameters/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Parameter BPJS', 'href' => route('bpjs-parameters.index')],
            ],
            'parameters' => $parameters,
            'defaults' => config('payroll.bpjs_defaults'),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('payroll.run'), 403);

        return Inertia::render('bpjs-parameters/create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Parameter BPJS', 'href' => route('bpjs-parameters.index')],
                ['title' => 'Tambah', 'href' => route('bpjs-parameters.create')],
            ],
            'defaults' => config('payroll.bpjs_defaults'),
        ]);
    }

    public function store(StoreBpjsParameterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        BpjsParameter::create([
            'effective_date' => $data['effective_date'],
            'kes_rate_employee' => $data['kes_rate_employee'],
            'kes_rate_employer' => $data['kes_rate_employer'],
            'kes_cap' => $data['kes_cap'],
            'tk_rates' => [
                'jht_employee' => (float) $data['jht_employee'],
                'jht_employer' => (float) $data['jht_employer'],
                'jkk' => (float) $data['jkk'],
                'jkm' => (float) $data['jkm'],
                'jp_employee' => (float) $data['jp_employee'],
                'jp_employer' => (float) $data['jp_employer'],
                'jp_cap' => (int) $data['jp_cap'],
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Parameter BPJS ditambahkan.']);

        return redirect()->route('bpjs-parameters.index');
    }

    public function destroy(Request $request, BpjsParameter $bpjsParameter): RedirectResponse
    {
        abort_unless($request->user()?->can('payroll.run'), 403);

        $bpjsParameter->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Parameter BPJS dihapus.']);

        return back();
    }
}
