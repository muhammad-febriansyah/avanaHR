<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalaryStructure\StoreSalaryStructureRequest;
use App\Http\Requests\SalaryStructure\UpdateSalaryStructureRequest;
use App\Models\JobGrade;
use App\Models\SalaryStructure;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SalaryStructureController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', SalaryStructure::class);

        $structures = SalaryStructure::query()
            ->with('jobGrade:id,code,name')
            ->get(['id', 'job_grade_id', 'band_min', 'band_max', 'currency'])
            ->sortBy(fn (SalaryStructure $s) => $s->jobGrade?->code)
            ->map(fn (SalaryStructure $s): array => [
                'id' => $s->id,
                'job_grade_id' => $s->job_grade_id,
                'grade_code' => $s->jobGrade?->code,
                'grade_name' => $s->jobGrade?->name,
                'band_min' => (int) $s->band_min,
                'band_max' => (int) $s->band_max,
                'currency' => $s->currency,
            ])
            ->values();

        // Grades without a band yet (selectable when creating).
        $usedGradeIds = $structures->pluck('job_grade_id')->all();

        return Inertia::render('salary-structures/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Struktur Gaji', 'href' => route('salary-structures.index')],
            ],
            'structures' => $structures,
            'grades' => JobGrade::query()
                ->whereNotIn('id', $usedGradeIds)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (JobGrade $g): array => ['id' => $g->id, 'label' => $g->code.' — '.$g->name]),
        ]);
    }

    public function store(StoreSalaryStructureRequest $request): RedirectResponse
    {
        $data = $request->validated();

        SalaryStructure::create([
            'job_grade_id' => $data['job_grade_id'],
            'band_min' => $data['band_min'],
            'band_max' => $data['band_max'],
            'currency' => 'IDR',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Struktur gaji dibuat.']);

        return back();
    }

    public function update(UpdateSalaryStructureRequest $request, SalaryStructure $salaryStructure): RedirectResponse
    {
        $salaryStructure->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Struktur gaji diperbarui.']);

        return back();
    }

    public function destroy(SalaryStructure $salaryStructure): RedirectResponse
    {
        $this->authorize('delete', $salaryStructure);

        $salaryStructure->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Struktur gaji dihapus.']);

        return back();
    }
}
