<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    use AuthorizesRequests;

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function baseCrumbs(): array
    {
        return [
            ['title' => 'Dashboard', 'href' => route('dashboard')],
            ['title' => 'Perusahaan', 'href' => route('companies.index')],
        ];
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::query()
            ->withCount(['branches', 'departments'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'npwp', 'phone', 'email'])
            ->map(fn (Company $company): array => [
                'id' => $company->id,
                'code' => $company->code,
                'name' => $company->name,
                'npwp' => $company->npwp,
                'phone' => $company->phone,
                'email' => $company->email,
                'branches_count' => $company->branches_count,
                'departments_count' => $company->departments_count,
            ]);

        return Inertia::render('companies/index', [
            'companies' => $companies,
            'breadcrumbs' => $this->baseCrumbs(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Company::class);

        return Inertia::render('companies/create', [
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => 'Tambah', 'href' => route('companies.create')]],
        ]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        Company::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perusahaan berhasil ditambahkan.']);

        return to_route('companies.index');
    }

    public function edit(Company $company): Response
    {
        $this->authorize('update', $company);

        return Inertia::render('companies/edit', [
            'company' => $company->only(['id', 'code', 'name', 'npwp', 'address', 'phone', 'email']),
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => $company->name, 'href' => route('companies.edit', $company)]],
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perusahaan berhasil diperbarui.']);

        return to_route('companies.index');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        if ($company->branches()->exists() || $company->departments()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Perusahaan masih memiliki cabang atau departemen.']);

            return back();
        }

        $company->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perusahaan berhasil dihapus.']);

        return back();
    }
}
