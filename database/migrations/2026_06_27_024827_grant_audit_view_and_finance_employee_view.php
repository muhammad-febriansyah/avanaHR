<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Grants the new tenant-level RBAC tweaks to existing databases:
 *  - audit.view to hr-admin / tenant-admin / auditor / super-admin
 *  - employee.view to finance
 * Idempotent and team-agnostic (applies to every tenant's role rows).
 */
return new class extends Migration
{
    public function up(): void
    {
        $audit = DB::table('permissions')->where(['name' => 'audit.view', 'guard_name' => 'web'])->value('id')
            ?? DB::table('permissions')->insertGetId([
                'name' => 'audit.view', 'guard_name' => 'web',
                'created_at' => now(), 'updated_at' => now(),
            ]);

        $employeeView = DB::table('permissions')->where(['name' => 'employee.view', 'guard_name' => 'web'])->value('id');

        $this->grant($audit, ['super-admin', 'hr-admin', 'tenant-admin', 'auditor']);

        if ($employeeView !== null) {
            $this->grant($employeeView, ['finance']);
        }
    }

    public function down(): void
    {
        $audit = DB::table('permissions')->where('name', 'audit.view')->value('id');
        if ($audit !== null) {
            DB::table('role_has_permissions')->where('permission_id', $audit)->delete();
            DB::table('permissions')->where('id', $audit)->delete();
        }
    }

    /**
     * @param  array<int, string>  $roleNames
     */
    private function grant(int $permissionId, array $roleNames): void
    {
        $roleIds = DB::table('roles')->whereIn('name', $roleNames)->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }
};
