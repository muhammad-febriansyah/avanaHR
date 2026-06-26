<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ApprovalDelegationController;
use App\Http\Controllers\ApprovalFlowController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AuditLogController as TenantAuditLogController;
use App\Http\Controllers\BankFileController;
use App\Http\Controllers\BenefitTypeController;
use App\Http\Controllers\BpjsParameterController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ComplianceReportController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeBenefitController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeHistoryController;
use App\Http\Controllers\EmployeeLifecycleEventController;
use App\Http\Controllers\EmployeeLoanController;
use App\Http\Controllers\EmployeeMovementController;
use App\Http\Controllers\EmployeeSalaryComponentController;
use App\Http\Controllers\EmployeeTaxBpjsController;
use App\Http\Controllers\Form1721A1Controller;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HrTicketController;
use App\Http\Controllers\JobGradeController;
use App\Http\Controllers\JobLevelController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OvertimeRequestController;
use App\Http\Controllers\PayrollComponentController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Platform\AuditLogController;
use App\Http\Controllers\Platform\BackupController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\Platform\ProvisioningController;
use App\Http\Controllers\Platform\SecurityEventController;
use App\Http\Controllers\Platform\SubscriptionController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\ReportBuilderController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalaryStructureController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SecuritySettingController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftPatternController;
use App\Http\Controllers\ShiftSwapController;
use App\Http\Controllers\ThrBonusRunController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkCalendarController;
use App\Http\Controllers\WorkVisitController;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('employees', EmployeeController::class);
    Route::resource('companies', CompanyController::class)->except(['show']);
    Route::resource('cost-centers', CostCenterController::class)->except(['show']);
    Route::resource('job-levels', JobLevelController::class)->except(['show']);
    Route::resource('job-grades', JobGradeController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('users', UserController::class)->only(['index', 'edit', 'update']);
    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('audit-logs', [TenantAuditLogController::class, 'index'])->name('audit-logs.index');
    Route::post('impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');
    Route::get('organization/structure', [OrganizationController::class, 'structure'])
        ->name('organization.structure');
    Route::patch('organization/departments/{department}/reparent', [OrganizationController::class, 'reparent'])
        ->name('organization.departments.reparent');
    Route::resource('leave-types', LeaveTypeController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('leave-balances', LeaveBalanceController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('leave-requests', LeaveRequestController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('departments', DepartmentController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('positions', PositionController::class)
        ->only(['store', 'update', 'destroy']);
    Route::resource('shifts', ShiftController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('shift-patterns', ShiftPatternController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::get('schedules/generate', [ScheduleController::class, 'generatePage'])
        ->name('schedules.generate-roster');
    Route::post('schedules/generate', [ScheduleController::class, 'generate'])
        ->name('schedules.generate');
    Route::resource('schedules', ScheduleController::class)
        ->only(['index', 'store', 'destroy']);
    Route::resource('shift-swaps', ShiftSwapController::class)
        ->only(['index', 'store', 'destroy']);
    Route::resource('payroll-components', PayrollComponentController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('salary-structures', SalaryStructureController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('payroll-periods', PayrollPeriodController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::post('payroll-periods/{payrollPeriod}/close', [PayrollPeriodController::class, 'close'])
        ->name('payroll-periods.close');
    Route::post('payroll-periods/{payrollPeriod}/reopen', [PayrollPeriodController::class, 'reopen'])
        ->name('payroll-periods.reopen');
    Route::resource('payroll-runs', PayrollRunController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('payroll-runs/{payrollRun}/process', [PayrollRunController::class, 'process'])
        ->name('payroll-runs.process');
    Route::post('payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])
        ->name('payroll-runs.approve');
    Route::post('payroll-runs/{payrollRun}/revert', [PayrollRunController::class, 'revert'])
        ->name('payroll-runs.revert');
    Route::post('payroll-runs/{payrollRun}/pay', [PayrollRunController::class, 'pay'])
        ->name('payroll-runs.pay');
    Route::get('employees/{employee}/history', [EmployeeHistoryController::class, 'index'])
        ->name('employees.history');
    Route::get('employees/{employee}/tax-bpjs', [EmployeeTaxBpjsController::class, 'index'])
        ->name('employees.tax-bpjs.index');
    Route::get('employees/{employee}/tax-profiles/create', [EmployeeTaxBpjsController::class, 'createTax'])
        ->name('employees.tax-profiles.create');
    Route::get('employees/{employee}/bpjs-profiles/create', [EmployeeTaxBpjsController::class, 'createBpjs'])
        ->name('employees.bpjs-profiles.create');
    Route::post('employees/{employee}/tax-profiles', [EmployeeTaxBpjsController::class, 'storeTax'])
        ->name('employees.tax-profiles.store');
    Route::delete('tax-profiles/{taxProfile}', [EmployeeTaxBpjsController::class, 'destroyTax'])
        ->name('employees.tax-profiles.destroy');
    Route::post('employees/{employee}/bpjs-profiles', [EmployeeTaxBpjsController::class, 'storeBpjs'])
        ->name('employees.bpjs-profiles.store');
    Route::delete('bpjs-profiles/{bpjsProfile}', [EmployeeTaxBpjsController::class, 'destroyBpjs'])
        ->name('employees.bpjs-profiles.destroy');
    Route::get('employees/{employee}/salary', [EmployeeSalaryComponentController::class, 'index'])
        ->name('employees.salary.index');
    Route::post('employees/{employee}/salary', [EmployeeSalaryComponentController::class, 'store'])
        ->name('employees.salary.store');
    Route::patch('salary-components/{salaryComponent}', [EmployeeSalaryComponentController::class, 'update'])
        ->name('employees.salary.update');
    Route::delete('salary-components/{salaryComponent}', [EmployeeSalaryComponentController::class, 'destroy'])
        ->name('employees.salary.destroy');
    Route::resource('payslips', PayslipController::class)
        ->only(['index', 'show']);
    Route::get('payslips/{payslip}/print', [PayslipController::class, 'print'])
        ->name('payslips.print');
    Route::resource('bank-files', BankFileController::class)
        ->only(['index', 'store', 'destroy']);
    Route::resource('bpjs-parameters', BpjsParameterController::class)
        ->only(['index', 'create', 'store', 'destroy']);
    Route::resource('reimbursements', ReimbursementController::class)
        ->only(['index', 'create', 'store', 'update', 'destroy']);
    Route::resource('employee-loans', EmployeeLoanController::class)
        ->only(['index', 'create', 'store', 'update', 'destroy']);
    Route::resource('thr-bonus-runs', ThrBonusRunController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('work-calendars', WorkCalendarController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::put('work-calendars/{workCalendar}/default', [WorkCalendarController::class, 'setDefault'])
        ->name('work-calendars.default');
    Route::resource('holidays', HolidayController::class)
        ->only(['store', 'update', 'destroy']);
    Route::resource('benefit-types', BenefitTypeController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('employee-benefits', EmployeeBenefitController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('employee-benefits/{employeeBenefit}/claims', [EmployeeBenefitController::class, 'storeClaim'])
        ->name('employee-benefits.claims.store');
    Route::delete('benefit-claims/{benefitClaim}', [EmployeeBenefitController::class, 'destroyClaim'])
        ->name('employee-benefits.claims.destroy');

    Route::resource('work-visits', WorkVisitController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('work-visits/{workVisit}/reports', [WorkVisitController::class, 'storeReport'])
        ->name('work-visits.reports.store');
    Route::delete('work-visits/{workVisit}/reports/{report}', [WorkVisitController::class, 'destroyReport'])
        ->name('work-visits.reports.destroy');

    Route::resource('overtime-requests', OvertimeRequestController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::resource('employee-documents', EmployeeDocumentController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('lifecycle', EmployeeLifecycleEventController::class)
        ->only(['index', 'create', 'store', 'destroy']);
    Route::resource('movements', EmployeeMovementController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('movements/{movement}/apply', [EmployeeMovementController::class, 'apply'])
        ->name('movements.apply');
    Route::post('movements/{movement}/schedule', [EmployeeMovementController::class, 'schedule'])
        ->name('movements.schedule');
    Route::post('movements/{movement}/unschedule', [EmployeeMovementController::class, 'unschedule'])
        ->name('movements.unschedule');
    Route::patch('clearance-items/{clearanceItem}', [EmployeeMovementController::class, 'updateClearance'])
        ->name('movements.clearance.update');
    // Approver inbox (generic approval engine runtime).
    Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('approvals/{approvalRequest}', [ApprovalController::class, 'show'])->name('approvals.show');
    Route::post('approvals/{approvalRequest}/act', [ApprovalController::class, 'act'])->name('approvals.act');

    // Self-service approval delegation.
    Route::get('approval-delegations', [ApprovalDelegationController::class, 'index'])
        ->name('approval-delegations.index');
    Route::post('approval-delegations', [ApprovalDelegationController::class, 'store'])
        ->name('approval-delegations.store');
    Route::delete('approval-delegations/{approvalDelegation}', [ApprovalDelegationController::class, 'destroy'])
        ->name('approval-delegations.destroy');

    Route::resource('approval-flows', ApprovalFlowController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::patch('approval-flows/{approvalFlow}/conditions', [ApprovalFlowController::class, 'updateConditions'])
        ->name('approval-flows.conditions');
    Route::resource('custom-fields', CustomFieldController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('branding', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::post('branding', [BrandingController::class, 'update'])->name('branding.update');
    Route::get('security-settings', [SecuritySettingController::class, 'edit'])
        ->name('security-settings.edit');
    Route::put('security-settings', [SecuritySettingController::class, 'update'])
        ->name('security-settings.update');
    Route::post('approval-flows/{approvalFlow}/steps', [ApprovalFlowController::class, 'storeStep'])
        ->name('approval-flows.steps.store');
    Route::delete('approval-flow-steps/{step}', [ApprovalFlowController::class, 'destroyStep'])
        ->name('approval-flows.steps.destroy');
    Route::get('analytics/workforce', [AnalyticsController::class, 'workforce'])
        ->name('analytics.workforce');
    Route::get('analytics/executive', [AnalyticsController::class, 'executive'])
        ->name('analytics.executive');
    Route::get('reports/compliance', [ComplianceReportController::class, 'index'])
        ->name('reports.compliance');
    Route::get('reports/annual-tax', [Form1721A1Controller::class, 'index'])
        ->name('reports.annual-tax');
    Route::get('reports/annual-tax/print', [Form1721A1Controller::class, 'print'])
        ->name('reports.annual-tax.print');
    Route::get('report-builder', [ReportBuilderController::class, 'index'])->name('report-builder.index');
    Route::post('report-builder', [ReportBuilderController::class, 'store'])->name('report-builder.store');
    Route::get('report-builder/{reportDefinition}/run', [ReportBuilderController::class, 'run'])
        ->name('report-builder.run');
    Route::delete('report-builder/{reportDefinition}', [ReportBuilderController::class, 'destroy'])
        ->name('report-builder.destroy');

    // HR helpdesk / ticketing.
    Route::get('hr-tickets', [HrTicketController::class, 'index'])->name('hr-tickets.index');
    Route::get('hr-tickets/create', [HrTicketController::class, 'create'])->name('hr-tickets.create');
    Route::post('hr-tickets', [HrTicketController::class, 'store'])->name('hr-tickets.store');
    Route::get('hr-tickets/{ticket}', [HrTicketController::class, 'show'])->name('hr-tickets.show');
    Route::post('hr-tickets/{ticket}/messages', [HrTicketController::class, 'reply'])
        ->name('hr-tickets.reply');
    Route::patch('hr-tickets/{ticket}', [HrTicketController::class, 'update'])->name('hr-tickets.update');

    // Time & Attendance (M02) — HR/admin web. Employee clock-in is mobile (Flutter).
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance-corrections', [AttendanceCorrectionController::class, 'index'])
        ->name('attendance-corrections.index');
    Route::resource('timesheets', TimesheetController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // Platform admin (super-admin only, cross-tenant).
    Route::middleware(EnsureSuperAdmin::class)
        ->prefix('platform')
        ->name('platform.')
        ->group(function () {
            Route::resource('tenants', TenantController::class);
            Route::post('tenants/{tenant}/impersonate', [ImpersonationController::class, 'start'])
                ->name('tenants.impersonate');
            Route::get('audit-logs', [AuditLogController::class, 'index'])
                ->name('audit-logs.index');
            Route::get('security-events', [SecurityEventController::class, 'index'])
                ->name('security-events.index');
            Route::get('provisioning', [ProvisioningController::class, 'index'])
                ->name('provisioning.index');
            Route::post('provisioning/{tenant}/apply', [ProvisioningController::class, 'apply'])
                ->name('provisioning.apply');
            Route::get('subscriptions', [SubscriptionController::class, 'index'])
                ->name('subscriptions.index');
            Route::put('subscriptions/{tenant}', [SubscriptionController::class, 'update'])
                ->name('subscriptions.update');
            Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
            Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
            Route::get('backups/{backup}/download', [BackupController::class, 'download'])
                ->name('backups.download');
            Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])
                ->name('backups.restore');
        });
});

require __DIR__.'/settings.php';
