import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    ChartColumn,
    CircleUserRound,
    ClipboardCheck,
    CreditCard,
    DatabaseBackup,
    Fingerprint,
    LayoutDashboard,
    Plane,
    Rocket,
    ScrollText,
    Settings,
    ShieldAlert,
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
import { index as leaveBalancesIndex } from '@/routes/leave-balances';
import { index as leaveRequestsIndex } from '@/routes/leave-requests';
import { index as leaveTypesIndex } from '@/routes/leave-types';
import { index as overtimeRequestsIndex } from '@/routes/overtime-requests';
import { index as payrollComponentsIndex } from '@/routes/payroll-components';
import { index as payrollPeriodsIndex } from '@/routes/payroll-periods';
import { index as payrollRunsIndex } from '@/routes/payroll-runs';
import { index as payslipsIndex } from '@/routes/payslips';
import { index as reimbursementsIndex } from '@/routes/reimbursements';
import { index as shiftsIndex } from '@/routes/shifts';
import { index as thrBonusRunsIndex } from '@/routes/thr-bonus-runs';
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
                    { title: 'Data Karyawan', href: employees.index.url(), permission: 'employee.view' },
                    { title: 'Struktur Organisasi', href: organization.structure.url(), permission: 'employee.view' },
                    { title: 'Departemen & Posisi', href: departments.index.url(), permission: 'employee.view' },
                    { title: 'Cost Center', href: costCenters.index.url(), permission: 'employee.view' },
                    { title: 'Jenjang Jabatan', href: jobLevels.index.url(), permission: 'employee.view' },
                    { title: 'Grade Jabatan', href: jobGrades.index.url(), permission: 'employee.view' },
                    { title: 'Kalender Kerja', href: workCalendars.index.url(), permission: 'employee.view' },
                    { title: 'Lifecycle', href: '#', permission: 'employee.view' },
                    { title: 'Dokumen', href: '#', permission: 'employee.view' },
                    { title: 'Tiket HR', href: '#', permission: 'employee.view' },
                ],
            },
            {
                title: 'Absensi',
                href: '#',
                icon: Fingerprint,
                feature: 'attendance',
                children: [
                    { title: 'Absen Hari Ini', href: '#', permission: 'attendance.view' },
                    { title: 'Rekap Absensi', href: '#', permission: 'attendance.view' },
                    { title: 'Koreksi Absensi', href: '#', permission: 'attendance.view' },
                    { title: 'Jadwal & Shift', href: shiftsIndex(), permission: 'attendance.manage' },
                    { title: 'Timesheet', href: '#', permission: 'attendance.view' },
                    { title: 'Import Biometrik', href: '#', permission: 'attendance.manage' },
                ],
            },
            {
                title: 'Cuti & Lembur',
                href: '#',
                icon: Plane,
                feature: 'leave',
                children: [
                    { title: 'Pengajuan Cuti', href: leaveRequestsIndex(), permission: 'leave.view' },
                    { title: 'Saldo Cuti', href: leaveBalancesIndex(), permission: 'leave.view' },
                    { title: 'Jenis Cuti', href: leaveTypesIndex(), permission: 'setting.manage' },
                    { title: 'Lembur', href: overtimeRequestsIndex(), permission: 'attendance.view' },
                ],
            },
            {
                title: 'Payroll',
                href: '#',
                icon: Wallet,
                feature: 'payroll',
                children: [
                    { title: 'Periode Payroll', href: payrollPeriodsIndex(), permission: 'payroll.view' },
                    { title: 'Proses Payroll', href: payrollRunsIndex(), permission: 'payroll.run' },
                    { title: 'Slip Gaji', href: payslipsIndex(), permission: 'payroll.view' },
                    { title: 'Komponen Gaji', href: payrollComponentsIndex(), permission: 'payroll.view' },
                    { title: 'Reimbursement', href: reimbursementsIndex(), permission: 'payroll.view' },
                    { title: 'Pinjaman', href: employeeLoans.index.url(), permission: 'payroll.view' },
                    { title: 'THR & Bonus', href: thrBonusRunsIndex(), permission: 'payroll.run' },
                    { title: 'File Bank', href: bankFiles.index.url(), permission: 'payroll.approve' },
                ],
            },
        ],
    },
    {
        label: 'Personal',
        items: [
            {
                title: 'Self-Service',
                href: '#',
                icon: CircleUserRound,
                feature: 'ess',
                children: [
                    { title: 'Dashboard Saya', href: '#' },
                    { title: 'Profil', href: '#' },
                    { title: 'Slip Gaji Saya', href: '#' },
                    { title: 'Cuti Saya', href: '#' },
                    { title: 'Klaim', href: '#' },
                    { title: 'Status Pengajuan', href: '#' },
                ],
            },
            {
                title: 'Persetujuan',
                href: '#',
                icon: ClipboardCheck,
                permission: 'leave.approve',
                feature: 'approval',
                children: [
                    { title: 'Inbox Approval', href: '#', permission: 'leave.approve' },
                    { title: 'Tim Saya', href: '#', permission: 'leave.approve' },
                    { title: 'Kalender Tim', href: '#', permission: 'leave.approve' },
                ],
            },
        ],
    },
    {
        label: 'Sistem',
        items: [
            {
                title: 'Laporan',
                href: '#',
                icon: ChartColumn,
                feature: 'analytics',
                children: [
                    { title: 'Analitik Workforce', href: '#', permission: 'report.view' },
                    { title: 'Dashboard Eksekutif', href: '#', permission: 'report.view' },
                    { title: 'Report Builder', href: '#', permission: 'report.export' },
                    { title: 'Laporan Kepatuhan', href: '#', permission: 'report.view' },
                ],
            },
            {
                title: 'Pengaturan',
                href: '#',
                icon: Settings,
                permission: 'setting.manage',
                feature: 'settings',
                children: [
                    { title: 'Perusahaan', href: companies.index.url(), permission: 'setting.manage' },
                    { title: 'Pengguna', href: users.index.url(), permission: 'setting.manage' },
                    { title: 'Hak Akses (Role)', href: roles.index.url(), permission: 'setting.manage' },
                    { title: 'Permission', href: permissions.index.url(), permission: 'setting.manage' },
                    { title: 'Workflow Approval', href: '#', permission: 'setting.manage' },
                    { title: 'Custom Field', href: '#', permission: 'setting.manage' },
                    { title: 'Keamanan', href: '#', permission: 'setting.manage' },
                    { title: 'Integrasi', href: '#', permission: 'setting.manage' },
                    { title: 'Langganan', href: '#', permission: 'setting.manage' },
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
            { title: 'Langganan & Paket', href: '#', icon: CreditCard },
            { title: 'Provisioning', href: '#', icon: Rocket },
        ],
    },
    {
        label: 'Keamanan & Audit',
        items: [
            { title: 'Audit Log', href: '#', icon: ScrollText },
            { title: 'Security Events', href: '#', icon: ShieldAlert },
            { title: 'Backup & Restore', href: '#', icon: DatabaseBackup },
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
