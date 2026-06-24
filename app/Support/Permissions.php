<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;

class Permissions
{
    /**
     * Human labels per permission module prefix (text before the dot).
     *
     * @var array<string, string>
     */
    private const MODULE_LABELS = [
        'employee' => 'Karyawan',
        'attendance' => 'Absensi',
        'leave' => 'Cuti & Lembur',
        'payroll' => 'Payroll',
        'report' => 'Laporan',
        'setting' => 'Pengaturan',
    ];

    /**
     * Human labels per permission action (text after the dot).
     *
     * @var array<string, string>
     */
    private const ACTION_LABELS = [
        'view' => 'Lihat',
        'view_sensitive' => 'Lihat Data Sensitif',
        'create' => 'Tambah',
        'update' => 'Ubah',
        'delete' => 'Hapus',
        'manage' => 'Kelola',
        'approve' => 'Setujui',
        'run' => 'Proses',
        'export' => 'Ekspor',
    ];

    /**
     * All permissions grouped by module for the role permission matrix.
     *
     * @return list<array{key: string, label: string, permissions: list<array{name: string, label: string}>}>
     */
    public static function grouped(): array
    {
        $names = Permission::query()->orderBy('id')->pluck('name');

        $groups = [];

        foreach ($names as $name) {
            [$module, $action] = array_pad(explode('.', $name, 2), 2, '');

            $groups[$module] ??= [
                'key' => $module,
                'label' => self::MODULE_LABELS[$module] ?? ucfirst($module),
                'permissions' => [],
            ];

            $groups[$module]['permissions'][] = [
                'name' => $name,
                'label' => self::ACTION_LABELS[$action] ?? ucfirst(str_replace('_', ' ', $action)),
            ];
        }

        return array_values($groups);
    }
}
