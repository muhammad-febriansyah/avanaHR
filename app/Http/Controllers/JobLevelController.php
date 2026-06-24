<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobLevel\StoreJobLevelRequest;
use App\Http\Requests\JobLevel\UpdateJobLevelRequest;
use App\Models\JobLevel;
use App\Models\Position;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class JobLevelController extends Controller
{
    use AuthorizesRequests;

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function baseCrumbs(): array
    {
        return [
            ['title' => 'Dashboard', 'href' => route('dashboard')],
            ['title' => 'Jenjang Jabatan', 'href' => route('job-levels.index')],
        ];
    }

    public function index(): Response
    {
        $this->authorize('viewAny', JobLevel::class);

        $jobLevels = JobLevel::query()
            ->orderBy('order')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'order'])
            ->map(fn (JobLevel $jobLevel): array => [
                'id' => $jobLevel->id,
                'code' => $jobLevel->code,
                'name' => $jobLevel->name,
                'order' => $jobLevel->order,
            ]);

        return Inertia::render('job-levels/index', [
            'jobLevels' => $jobLevels,
            'breadcrumbs' => $this->baseCrumbs(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', JobLevel::class);

        return Inertia::render('job-levels/create', [
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => 'Tambah', 'href' => route('job-levels.create')]],
        ]);
    }

    public function store(StoreJobLevelRequest $request): RedirectResponse
    {
        JobLevel::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenjang jabatan berhasil ditambahkan.']);

        return to_route('job-levels.index');
    }

    public function edit(JobLevel $jobLevel): Response
    {
        $this->authorize('update', $jobLevel);

        return Inertia::render('job-levels/edit', [
            'jobLevel' => $jobLevel->only(['id', 'code', 'name', 'order']),
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => $jobLevel->name, 'href' => route('job-levels.edit', $jobLevel)]],
        ]);
    }

    public function update(UpdateJobLevelRequest $request, JobLevel $jobLevel): RedirectResponse
    {
        $jobLevel->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenjang jabatan berhasil diperbarui.']);

        return to_route('job-levels.index');
    }

    public function destroy(JobLevel $jobLevel): RedirectResponse
    {
        $this->authorize('delete', $jobLevel);

        $inUse = Position::query()
            ->where('job_level_id', $jobLevel->id)
            ->exists();

        if ($inUse) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Jenjang jabatan masih dipakai posisi.']);

            return back();
        }

        $jobLevel->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenjang jabatan berhasil dihapus.']);

        return back();
    }
}
