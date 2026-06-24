<?php

/*
|--------------------------------------------------------------------------
| Tenant Features / Modules
|--------------------------------------------------------------------------
|
| Catalog of toggleable modules per tenant. The super-admin enables/disables
| these per tenant; the value is stored in tenant_subscriptions.feature_flags
| and drives both the sidebar menu and (future) route access.
|
*/

return [
    'catalog' => [
        ['key' => 'hr_core', 'label' => 'HR Core & Karyawan'],
        ['key' => 'attendance', 'label' => 'Absensi'],
        ['key' => 'leave', 'label' => 'Cuti & Lembur'],
        ['key' => 'payroll', 'label' => 'Payroll'],
        ['key' => 'ess', 'label' => 'Self-Service (ESS)'],
        ['key' => 'approval', 'label' => 'Persetujuan'],
        ['key' => 'analytics', 'label' => 'Laporan & Analitik'],
        ['key' => 'settings', 'label' => 'Pengaturan'],
    ],
];
