<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Support\CurrentTenant;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Roles seeded by the platform — name is locked and they cannot be deleted,
     * but their permissions remain editable per tenant.
     *
     * @var list<string>
     */
    private const SYSTEM_ROLES = [
        'tenant-admin', 'hr-admin', 'payroll-officer', 'finance', 'manager', 'employee', 'auditor',
    ];

    private function tenantId(): int
    {
        return app(CurrentTenant::class)->id()
            ?? abort(403, 'Manajemen role hanya tersedia dalam konteks tenant.');
    }

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function baseCrumbs(): array
    {
        return [
            ['title' => 'Dashboard', 'href' => route('dashboard')],
            ['title' => 'Hak Akses (Role)', 'href' => route('roles.index')],
        ];
    }

    /**
     * Reject roles that do not belong to the current tenant (IDOR guard).
     */
    private function ensureOwned(Role $role): void
    {
        abort_unless($role->team_id === $this->tenantId(), 404);
    }

    public function index(): Response
    {
        abort_unless(request()->user()->can('setting.manage'), 403);

        $roles = Role::query()
            ->where('team_id', $this->tenantId())
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'is_system' => in_array($role->name, self::SYSTEM_ROLES, true),
            ]);

        return Inertia::render('roles/index', [
            'roles' => $roles,
            'breadcrumbs' => $this->baseCrumbs(),
        ]);
    }

    public function create(): Response
    {
        abort_unless(request()->user()->can('setting.manage'), 403);

        return Inertia::render('roles/create', [
            'permissionGroups' => Permissions::grouped(),
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => 'Tambah', 'href' => route('roles.create')]],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
            'team_id' => $this->tenantId(),
        ]);

        $role->syncPermissions($request->validated('permissions') ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role berhasil ditambahkan.']);

        return to_route('roles.index');
    }

    public function edit(Role $role): Response
    {
        abort_unless(request()->user()->can('setting.manage'), 403);
        $this->ensureOwned($role);

        return Inertia::render('roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_system' => in_array($role->name, self::SYSTEM_ROLES, true),
                'permissions' => $role->permissions->pluck('name')->all(),
            ],
            'permissionGroups' => Permissions::grouped(),
            'breadcrumbs' => [...$this->baseCrumbs(), ['title' => $role->name, 'href' => route('roles.edit', $role)]],
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->ensureOwned($role);

        // System role names are immutable; only their permission set may change.
        if (! in_array($role->name, self::SYSTEM_ROLES, true)) {
            $role->update(['name' => $request->validated('name')]);
        }

        $role->syncPermissions($request->validated('permissions') ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role berhasil diperbarui.']);

        return to_route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_unless(request()->user()->can('setting.manage'), 403);
        $this->ensureOwned($role);

        if (in_array($role->name, self::SYSTEM_ROLES, true)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Role bawaan sistem tidak dapat dihapus.']);

            return back();
        }

        if ($role->users()->count() > 0) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Role masih dipakai pengguna.']);

            return back();
        }

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role berhasil dihapus.']);

        return back();
    }
}
