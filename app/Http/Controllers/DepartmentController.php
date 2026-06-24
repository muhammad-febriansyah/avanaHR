<?php

namespace App\Http\Controllers;

use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\Position;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->withCount('positions')
            ->with('parent:id,name')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'parent_id'])
            ->map(fn (Department $department): array => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'parent_id' => $department->parent_id,
                'parent_name' => $department->parent?->name,
                'positions_count' => $department->positions_count,
            ]);

        $positions = Position::query()
            ->with(['department:id,name', 'jobLevel:id,name', 'jobGrade:id,name'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'department_id', 'job_level_id', 'job_grade_id'])
            ->map(fn (Position $position): array => [
                'id' => $position->id,
                'code' => $position->code,
                'name' => $position->name,
                'department_id' => $position->department_id,
                'department_name' => $position->department?->name,
                'job_level_id' => $position->job_level_id,
                'job_level_name' => $position->jobLevel?->name,
                'job_grade_id' => $position->job_grade_id,
                'job_grade_name' => $position->jobGrade?->name,
            ]);

        return Inertia::render('org-units/index', [
            'departments' => $departments,
            'positions' => $positions,
            'options' => [
                'departments' => Department::orderBy('name')->get(['id', 'name']),
                'jobLevels' => JobLevel::orderBy('name')->get(['id', 'name']),
                'jobGrades' => JobGrade::orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::create([
            ...$request->validated(),
            'company_id' => Company::query()->value('id'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Departemen berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Departemen berhasil diperbarui.']);

        return back();
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        if ($department->positions()->exists() || $department->children()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Departemen masih memiliki posisi atau sub-departemen.']);

            return back();
        }

        $department->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Departemen berhasil dihapus.']);

        return back();
    }
}
