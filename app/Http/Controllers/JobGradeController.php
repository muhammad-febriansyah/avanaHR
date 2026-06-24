<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobGrade\StoreJobGradeRequest;
use App\Http\Requests\JobGrade\UpdateJobGradeRequest;
use App\Models\EmployeeEmployment;
use App\Models\JobGrade;
use App\Models\Position;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class JobGradeController extends Controller
{
    use AuthorizesRequests;

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function baseCrumbs(): array
    {
        return [
            ['title' => 'Dashboard', 'href' => route('dashboard')],
            ['title' => 'Grade Jabatan', 'href' => route('job-grades.index')],
        ];
    }

    public function index(): Response
    {
        $this->authorize('viewAny', JobGrade::class);

        $jobGrades = JobGrade::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'salary_band_min', 'salary_band_max'])
            ->map(fn (JobGrade $jobGrade): array => [
                'id' => $jobGrade->id,
                'code' => $jobGrade->code,
                'name' => $jobGrade->name,
                'salary_band_min' => $jobGrade->salary_band_min,
                'salary_band_max' => $jobGrade->salary_band_max,
            ]);

        return Inertia::render('job-grades/index', [
            'jobGrades' => $jobGrades,
            'breadcrumbs' => $this->baseCrumbs(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', JobGrade::class);

        return Inertia::render('job-grades/create', [
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => 'Tambah', 'href' => route('job-grades.create')]],
        ]);
    }

    public function store(StoreJobGradeRequest $request): RedirectResponse
    {
        JobGrade::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Grade jabatan berhasil ditambahkan.']);

        return to_route('job-grades.index');
    }

    public function edit(JobGrade $jobGrade): Response
    {
        $this->authorize('update', $jobGrade);

        return Inertia::render('job-grades/edit', [
            'jobGrade' => $jobGrade->only(['id', 'code', 'name', 'salary_band_min', 'salary_band_max']),
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => $jobGrade->name, 'href' => route('job-grades.edit', $jobGrade)]],
        ]);
    }

    public function update(UpdateJobGradeRequest $request, JobGrade $jobGrade): RedirectResponse
    {
        $jobGrade->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Grade jabatan berhasil diperbarui.']);

        return to_route('job-grades.index');
    }

    public function destroy(JobGrade $jobGrade): RedirectResponse
    {
        $this->authorize('delete', $jobGrade);

        $inUse = Position::query()->where('job_grade_id', $jobGrade->id)->exists()
            || EmployeeEmployment::query()->where('job_grade_id', $jobGrade->id)->exists();

        if ($inUse) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Grade jabatan masih dipakai posisi atau penempatan karyawan.']);

            return back();
        }

        $jobGrade->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Grade jabatan berhasil dihapus.']);

        return back();
    }
}
