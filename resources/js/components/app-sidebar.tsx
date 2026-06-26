import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    ChartColumn,
    CreditCard,
    DatabaseBackup,
    Fingerprint,
    Inbox,
    LayoutDashboard,
    Plane,
    Rocket,
    ScrollText,
    Settings,
    ShieldAlert,
    UserCog,
    Users,
    Wallet,
} from 'lucide-react';
import bankFiles from '@/actions/App/Http/Controllers/BankFileController';
import companies from '@/actions/App/Http/Controllers/CompanyController';
import costCenters from '@/actions/App/Http/Controllers/CostCenterController';
import employeeLoans from '@/actions/App/Http/Controllers/EmployeeLoanController';
import departments from '@/actions/App/Http/Controllers/DepartmentController';
import employees from '@/actions/App/Http/Controllers/EmployeeController';
import jobGrades from '@/actions/App/Http/Controllers/JobGradeController';
import jobLevels from '@/actions/App/Http/Controllers/JobLevelController';
import organization from '@/actions/App/Http/Controllers/OrganizationController';
import permissions from '@/actions/App/Http/Controllers/PermissionController';
import roles from '@/actions/App/Http/Controllers/RoleController';
import users from '@/actions/App/Http/Controllers/UserController';
import workCalendars from '@/actions/App/Http/Controllers/WorkCalendarController';
import auditLogs from '@/actions/App/Http/Controllers/Platform/AuditLogController';
import backups from '@/actions/App/Http/Controllers/Platform/BackupController';
import provisioning from '@/actions/App/Http/Controllers/Platform/ProvisioningController';
import securityEvents from '@/actions/App/Http/Controllers/Platform/SecurityEventController';
import subscriptions from '@/actions/App/Http/Controllers/Platform/SubscriptionController';
import tenants from '@/actions/App/Http/Controllers/Platform/TenantController';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavSupport } from '@/components/nav-support';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import {
    executive as analyticsExecutive,
    workforce as analyticsWorkforce,
} from '@/routes/analytics';
import { index as approvalDelegationsIndex } from '@/routes/approval-delegations';
import { index as approvalFlowsIndex } from '@/routes/approval-flows';
import { index as approvalsIndex } from '@/routes/approvals';
import { index as benefitTypesIndex } from '@/routes/benefit-types';
import { index as bpjsParametersIndex } from '@/routes/bpjs-parameters';
import { edit as brandingEdit } from '@/routes/branding';
import { index as employeeBenefitsIndex } from '@/routes/employee-benefits';
import { index as attendanceIndex } from '@/routes/attendance';
import { index as attendanceCorrectionsIndex } from '@/routes/attendance-corrections';
import { index as employeeDocumentsIndex } from '@/routes/employee-documents';
import { index as hrTicketsIndex } from '@/routes/hr-tickets';
import { index as leaveBalancesIndex } from '@/routes/leave-balances';
import { index as lifecycleIndex } from '@/routes/lifecycle';
import { index as movementsIndex } from '@/routes/movements';
import { index as leaveRequestsIndex } from '@/routes/leave-requests';
import { index as leaveTypesIndex } from '@/routes/leave-types';
import { index as overtimeRequestsIndex } from '@/routes/overtime-requests';
import { index as payrollComponentsIndex } from '@/routes/payroll-components';
import { index as payrollPeriodsIndex } from '@/routes/payroll-periods';
import { index as payrollRunsIndex } from '@/routes/payroll-runs';
import { index as payslipsIndex } from '@/routes/payslips';
import { index as reimbursementsIndex } from '@/routes/reimbursements';
import { index as reportBuilderIndex } from '@/routes/report-builder';
import {
    annualTax as reportsAnnualTax,
    compliance as reportsCompliance,
} from '@/routes/reports';
import { index as salaryStructuresIndex } from '@/routes/salary-structures';
import { index as schedulesIndex } from '@/routes/schedules';
import { edit as securitySettingsEdit } from '@/routes/security-settings';
import { index as shiftPatternsIndex } from '@/routes/shift-patterns';
import { index as shiftsIndex } from '@/routes/shifts';
import { index as thrBonusRunsIndex } from '@/routes/thr-bonus-runs';
import { index as timesheetsIndex } from '@/routes/timesheets';
import { index as workVisitsIndex } from '@/routes/work-visits';
import type { NavItem } from '@/types';

/**
 * AvanaHR navigation. Routes for not-yet-built modules point to '#'
 * and will be wired to named Inertia routes as each module ships.
 */
type NavGroup = { label?: string; items: NavItem[] };

const navGroups: NavGroup[] = [
    {
        items: [
            { title: 'Dashboard', href: dashboard(), icon: LayoutDashboard },
            {
                title: 'Inbox Approval',
                href: approvalsIndex(),
                icon: Inbox,
                permission: 'approval.act',
            },
            {
                title: 'Delegasi Approval',
                href: approvalDelegationsIndex(),
                icon: UserCog,
                permission: 'approval.act',
            },
        ],
    },
    {
        label: 'Manajemen',
        items: [
            {
                title: 'Karyawan',
                href: '#',
                icon: Users,
                feature: 'hr_core',
                children: [
                    {
                        title: 'Data Karyawan',
                        href: employees.index.url(),
                        permission: 'employee.view',
                    },
                    {
                        title: 'Struktur Organisasi',
                        href: organization.structure.url(),
                        permission: 'employee.view',
                    },
                    {
                        title: 'Departemen & Posisi',
                        href: departments.index.url(),
                        permission: 'employee.view',
                    },
                    {
                        title: 'Cost Center',
                        href: costCenters.index.url(),
                        permission: 'employee.view',
                    },
                    {
                        title: 'Jenjang Jabatan',
                        href: jobLevels.index.url(),
                        permission: 'employee.view',
                    },
                    {
                        title: 'Grade Jabatan',
                        href: jobGrades.index.url(),
                        permission: 'employee.view',
                    },
                    {
                        title: 'Kalender Kerja',
                        href: workCalendars.index.url(),
                        permission: 'employee.view',
                    },
                    {
                        title: 'Lifecycle',
                        href: lifecycleIndex(),
                        permission: 'employee.view',
                    },
                    {
                        title: 'Mutasi & Movement',
                        href: movementsIndex(),
                        permission: 'employee.view',
                    },
                    {
                        title: 'Dokumen',
                        href: employeeDocumentsIndex(),
                        permission: 'employee.view',
                    },
                    {
                        title: 'Tiket HR',
                        href: hrTicketsIndex(),
                        permission: 'employee.view',
                    },
                ],
            },
            {
                title: 'Absensi',
                href: '#',
                icon: Fingerprint,
                feature: 'attendance',
                children: [
                    {
                        title: 'Rekap Absensi',
                        href: attendanceIndex(),
                        permission: 'attendance.view',
                    },
                    {
                        title: 'Koreksi Absensi',
                        href: attendanceCorrectionsIndex(),
                        permission: 'attendance.view',
                    },
                    {
                        title: 'Jadwal & Shift',
                        href: shiftsIndex(),
                        permission: 'attendance.manage',
                    },
                    {
                        title: 'Pola Shift',
                        href: shiftPatternsIndex(),
                        permission: 'attendance.manage',
                    },
                    {
                        title: 'Roster Shift',
                        href: schedulesIndex(),
                        permission: 'attendance.view',
                    },
                    {
                        title: 'Timesheet',
                        href: timesheetsIndex(),
                        permission: 'attendance.view',
                    },
                ],
            },
            {
                title: 'Cuti & Lembur',
                href: '#',
                icon: Plane,
                feature: 'leave',
                children: [
                    {
                        title: 'Pengajuan Cuti',
                        href: leaveRequestsIndex(),
                        permission: 'leave.view',
                    },
                    {
                        title: 'Saldo Cuti',
                        href: leaveBalancesIndex(),
                        permission: 'leave.view',
                    },
                    {
                        title: 'Jenis Cuti',
                        href: leaveTypesIndex(),
                        permission: 'setting.manage',
                    },
                    {
                        title: 'Lembur',
                        href: overtimeRequestsIndex(),
                        permission: 'attendance.view',
                    },
                    {
                        title: 'Kunjungan Kerja',
                        href: workVisitsIndex(),
                        permission: 'employee.view',
                    },
                ],
            },
            {
                title: 'Payroll',
                href: '#',
                icon: Wallet,
                feature: 'payroll',
                children: [
                    {
                        title: 'Periode Payroll',
                        href: payrollPeriodsIndex(),
                        permission: 'payroll.view',
                    },
                    {
                        title: 'Proses Payroll',
                        href: payrollRunsIndex(),
                        permission: 'payroll.run',
                    },
                    {
                        title: 'Slip Gaji',
                        href: payslipsIndex(),
                        permission: 'payroll.view',
                    },
                    {
                        title: 'Komponen Gaji',
                        href: payrollComponentsIndex(),
                        permission: 'payroll.view',
                    },
                    {
                        title: 'Struktur Gaji',
                        href: salaryStructuresIndex(),
                        permission: 'payroll.view',
                    },
                    {
                        title: 'Reimbursement',
                        href: reimbursementsIndex(),
                        permission: 'payroll.view',
                    },
                    {
                        title: 'Pinjaman',
                        href: employeeLoans.index.url(),
                        permission: 'payroll.view',
                    },
                    {
                        title: 'THR & Bonus',
                        href: thrBonusRunsIndex(),
                        permission: 'payroll.run',
                    },
                    {
                        title: 'File Bank',
                        href: bankFiles.index.url(),
                        permission: 'payroll.approve',
                    },
                    {
                        title: 'Benefit Karyawan',
                        href: employeeBenefitsIndex(),
                        permission: 'payroll.view',
                    },
                    {
                        title: 'Jenis Benefit',
                        href: benefitTypesIndex(),
                        permission: 'payroll.view',
                    },
                    {
                        title: 'Parameter BPJS',
                        href: bpjsParametersIndex(),
                        permission: 'payroll.view',
                    },
                ],
            },
        ],
    },
    // Grup "Personal" (Self-Service + Persetujuan) di-hide: ESS/MSS = Flutter
    // (karyawan via mobile). Approval manager sudah lewat halaman transaksi.
    {
        label: 'Sistem',
        items: [
            {
                title: 'Laporan',
                href: '#',
                icon: ChartColumn,
                feature: 'analytics',
                children: [
                    {
                        title: 'Analitik Workforce',
                        href: analyticsWorkforce(),
                        permission: 'report.view',
                    },
                    {
                        title: 'Dashboard Eksekutif',
                        href: analyticsExecutive(),
                        permission: 'report.view',
                    },
                    {
                        title: 'Report Builder',
                        href: reportBuilderIndex(),
                        permission: 'report.view',
                    },
                    {
                        title: 'Laporan Kepatuhan',
                        href: reportsCompliance(),
                        permission: 'report.view',
                    },
                    {
                        title: 'Bukti Potong 1721-A1',
                        href: reportsAnnualTax(),
                        permission: 'report.view',
                    },
                ],
            },
            {
                title: 'Pengaturan',
                href: '#',
                icon: Settings,
                permission: 'setting.manage',
                feature: 'settings',
                children: [
                    {
                        title: 'Perusahaan',
                        href: companies.index.url(),
                        permission: 'setting.manage',
                    },
                    {
                        title: 'Branding',
                        href: brandingEdit(),
                        permission: 'setting.manage',
                    },
                    {
                        title: 'Pengguna',
                        href: users.index.url(),
                        permission: 'setting.manage',
                    },
                    {
                        title: 'Hak Akses (Role)',
                        href: roles.index.url(),
                        permission: 'setting.manage',
                    },
                    {
                        title: 'Permission',
                        href: permissions.index.url(),
                        permission: 'setting.manage',
                    },
                    {
                        title: 'Workflow Approval',
                        href: approvalFlowsIndex(),
                        permission: 'setting.manage',
                    },
                    // Custom Field di-hide: baru CRUD definisi, render value di form
                    // Employee belum (setengah jadi). Route/halaman/test tetap ada.
                    {
                        title: 'Keamanan',
                        href: securitySettingsEdit(),
                        permission: 'setting.manage',
                    },
                    // Integrasi & Langganan di-hide: belum dibutuhkan.
                ],
            },
        ],
    },
];

// Platform super-admin navigation (cross-tenant). Distinct from the tenant menu:
// no HR operations, only platform/tenant administration.
const platformNavGroups: NavGroup[] = [
    {
        items: [
            { title: 'Dashboard', href: dashboard(), icon: LayoutDashboard },
        ],
    },
    {
        label: 'Platform',
        items: [
            { title: 'Tenant', href: tenants.index.url(), icon: Building2 },
            {
                title: 'Langganan & Paket',
                href: subscriptions.index.url(),
                icon: CreditCard,
            },
            {
                title: 'Provisioning',
                href: provisioning.index.url(),
                icon: Rocket,
            },
        ],
    },
    {
        label: 'Keamanan & Audit',
        items: [
            {
                title: 'Audit Log',
                href: auditLogs.index.url(),
                icon: ScrollText,
            },
            {
                title: 'Security Events',
                href: securityEvents.index.url(),
                icon: ShieldAlert,
            },
            {
                title: 'Backup & Restore',
                href: backups.index.url(),
                icon: DatabaseBackup,
            },
        ],
    },
];

/**
 * Keep only items the user is allowed to see. A parent stays visible when it
 * has at least one visible child; leaf items honor their own permission.
 */
function filterNav(
    items: NavItem[],
    can: (permission?: string) => boolean,
    hasFeature: (feature?: string) => boolean,
): NavItem[] {
    return items.reduce<NavItem[]>((visible, item) => {
        if (!hasFeature(item.feature)) {
            return visible;
        }

        if (item.children?.length) {
            const children = filterNav(item.children, can, hasFeature);

            if (children.length > 0) {
                visible.push({ ...item, children });
            }
        } else if (can(item.permission)) {
            visible.push(item);
        }

        return visible;
    }, []);
}

export function AppSidebar() {
    const { can } = usePermissions();
    const { auth, features } = usePage().props as unknown as {
        auth: { user?: { is_super_admin?: boolean } };
        features?: string[];
    };
    const isSuperAdmin = Boolean(auth?.user?.is_super_admin);
    const enabledFeatures = features ?? [];
    const hasFeature = (feature?: string): boolean =>
        !feature || enabledFeatures.includes(feature);

    // Super-admin has every permission + feature, so filterNav returns the full
    // tenant menu; combine it with the platform menu for full system control.
    const tenantGroups = navGroups
        .map((group) => ({
            ...group,
            items: filterNav(group.items, can, hasFeature),
        }))
        .filter((group) => group.items.length > 0);

    const visibleGroups = isSuperAdmin
        ? // Platform menu already has its own Dashboard; drop the tenant menu's
          // unlabeled Dashboard group to avoid duplicating it.
          [...platformNavGroups, ...tenantGroups.filter((group) => group.label)]
        : tenantGroups;

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="pt-3">
                {visibleGroups.map((group, i) => (
                    <NavMain
                        key={group.label ?? `group-${i}`}
                        label={group.label}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavSupport />
            </SidebarFooter>
        </Sidebar>
    );
}
