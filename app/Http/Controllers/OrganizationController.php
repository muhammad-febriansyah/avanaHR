<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Repositories\Organization\OrganizationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationRepository $organization) {}

    public function structure(Request $request): Response
    {
        abort_unless($request->user()->can('employee.view'), 403);

        return Inertia::render('organization/structure', [
            'tree' => $this->organization->tree(),
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Struktur Organisasi', 'href' => route('organization.structure')],
            ],
        ]);
    }

    /**
     * Move a department under a new parent (or to the company root when null).
     * Guards against cross-company moves, self-parenting, and cycles so the
     * tree never becomes corrupted.
     */
    public function reparent(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->can('employee.update'), 403);

        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                // Same tenant is enforced automatically by BelongsToTenant scope.
                'exists:departments,id',
            ],
        ]);

        $parentId = $validated['parent_id'] ?? null;

        if ($parentId !== null) {
            $parent = Department::query()->findOrFail($parentId);

            if ($parent->company_id !== $department->company_id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Departemen induk harus berada di perusahaan yang sama.',
                ]);
            }

            if ($parent->id === $department->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Departemen tidak bisa menjadi induk dirinya sendiri.',
                ]);
            }

            if ($this->isDescendantOf($parent, $department)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Tidak bisa memindahkan departemen ke bawah turunannya sendiri.',
                ]);
            }
        }

        $department->update(['parent_id' => $parentId]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Struktur organisasi diperbarui.']);

        return back();
    }

    /**
     * Walk the candidate parent's ancestor chain to detect whether moving
     * $department under $parent would create a cycle (i.e. the candidate
     * parent is the department itself or one of its descendants).
     */
    private function isDescendantOf(Department $parent, Department $department): bool
    {
        $current = $parent;

        while ($current !== null) {
            if ($current->id === $department->id) {
                return true;
            }

            $current = $current->parent_id !== null
                ? Department::query()->find($current->parent_id)
                : null;
        }

        return false;
    }
}
