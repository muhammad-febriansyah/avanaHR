<?php

namespace App\Http\Controllers;

use App\Http\Requests\BenefitType\StoreBenefitTypeRequest;
use App\Http\Requests\BenefitType\UpdateBenefitTypeRequest;
use App\Models\BenefitType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BenefitTypeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BenefitType::class);

        $filters = $request->only('search');

        $benefitTypes = BenefitType::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"),
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (BenefitType $benefitType): array => [
                'id' => $benefitType->id,
                'code' => $benefitType->code,
                'name' => $benefitType->name,
                'default_quota' => $benefitType->default_quota !== null ? (int) $benefitType->default_quota : null,
                'description' => $benefitType->description,
                'is_active' => (bool) $benefitType->is_active,
            ]);

        return Inertia::render('benefit-types/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Jenis Benefit', 'href' => route('benefit-types.index')],
            ],
            'benefitTypes' => $benefitTypes,
            'filters' => (object) $filters,
        ]);
    }

    public function store(StoreBenefitTypeRequest $request): RedirectResponse
    {
        BenefitType::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis benefit ditambahkan.']);

        return back();
    }

    public function update(UpdateBenefitTypeRequest $request, BenefitType $benefitType): RedirectResponse
    {
        $benefitType->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis benefit diperbarui.']);

        return back();
    }

    public function destroy(BenefitType $benefitType): RedirectResponse
    {
        $this->authorize('delete', $benefitType);

        if ($benefitType->employeeBenefits()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Jenis benefit dipakai, tidak bisa dihapus.']);

            return back();
        }

        $benefitType->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis benefit dihapus.']);

        return back();
    }
}
