<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRolesRequest;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private function tenantId(): int
    {
        return app(CurrentTenant::class)->id()
            ?? abort(403, 'Manajemen pengguna hanya tersedia dalam konteks tenant.');
    }

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function baseCrumbs(): array
    {
        return [
            ['title' => 'Dashboard', 'href' => route('dashboard')],
            ['title' => 'Pengguna', 'href' => route('users.index')],
        ];
    }

    /**
     * Reject users outside the current tenant or platform super-admins.
     */
    private function ensureManageable(User $user): void
    {
        abort_if($user->tenant_id !== $this->tenantId() || $user->is_super_admin, 404);
    }

    /**
     * @return list<string>
     */
    private function tenantRoleNames(): array
    {
        return Role::query()
            ->where('team_id', $this->tenantId())
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public function index(): Response
    {
        abort_unless(request()->user()->can('setting.manage'), 403);

        $users = User::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_super_admin', false)
            ->with(['employee:id,employee_no', 'roles:id,name'])
            ->orderBy('name')
            ->get(['id', 'employee_id', 'name', 'email', 'status'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'employee_no' => $user->employee?->employee_no,
                'roles' => $user->roles->pluck('name')->all(),
            ]);

        return Inertia::render('users/index', [
            'users' => $users,
            'breadcrumbs' => $this->baseCrumbs(),
        ]);
    }

    public function edit(User $user): Response
    {
        abort_unless(request()->user()->can('setting.manage'), 403);
        $this->ensureManageable($user);

        return Inertia::render('users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->all(),
            ],
            'roles' => $this->tenantRoleNames(),
            'isSelf' => $user->id === request()->user()->id,
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => $user->name, 'href' => route('users.edit', $user)]],
        ]);
    }

    public function update(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        $this->ensureManageable($user);

        // Guard against self-lockout: a user cannot change their own roles.
        if ($user->id === $request->user()->id) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Anda tidak dapat mengubah role akun sendiri.']);

            return back();
        }

        $user->syncRoles($request->validated('roles') ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role pengguna berhasil diperbarui.']);

        return to_route('users.index');
    }
}
