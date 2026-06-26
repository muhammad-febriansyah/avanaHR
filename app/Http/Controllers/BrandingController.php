<?php

namespace App\Http\Controllers;

use App\Http\Requests\Branding\UpdateBrandingRequest;
use App\Models\Company;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tenant branding: the company name + logo shown in the app and on payslips.
 * Always scoped to the current tenant's own company.
 */
class BrandingController extends Controller
{
    use AuthorizesRequests;

    public function edit(Request $request): Response
    {
        abort_unless($request->user()?->can('setting.manage'), 403);

        $company = Company::orderBy('id')->firstOrFail();

        return Inertia::render('branding/edit', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Branding', 'href' => route('branding.edit')],
            ],
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo_url' => $company->logo_path ? Storage::url($company->logo_path) : null,
            ],
        ]);
    }

    public function update(UpdateBrandingRequest $request): RedirectResponse
    {
        $company = Company::orderBy('id')->firstOrFail();

        $data = ['name' => $request->validated('name')];

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            // Per-tenant prefix keeps logos isolated on disk.
            $data['logo_path'] = $request->file('logo')->store('logos/'.$company->tenant_id, 'public');
        }

        $company->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Branding diperbarui.']);

        return back();
    }
}
