<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApprovalFlowController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\BankFileController;
use App\Http\Controllers\BenefitTypeController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ComplianceReportController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeBenefitController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeLifecycleEventController;
use App\Http\Controllers\EmployeeLoanController;
use App\Http\Controllers\EmployeeMovementController;
use App\Http\Controllers\EmployeeSalaryComponentController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HrTicketController;
use App\Http\Controllers\JobGradeController;
use App\Http\Controllers\JobLevelController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OvertimeRequestController;
use App\Http\Controllers\PayrollComponentController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Platform\AuditLogController;
use App\Http\Controllers\Platform\BackupController;
use App\Http\Controllers\Platform\ProvisioningController;
use App\Http\Controllers\Platform\SecurityEventController;
use App\Http\Controllers\Platform\SubscriptionController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\ReportBuilderController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SecuritySettingController;
use App\Http\Controllers\ShiftController;
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
    Route::get('organization/structure', [OrganizationController::class, 'structure'])
        ->name('organization.structure');
    Route::resource('leave-types', LeaveTypeController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('leave-balances', LeaveBalanceController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('leave-requests', LeaveRequestController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::patch('leave-requests/{leaveRequest}/decide', [LeaveRequestController::class, 'decide'])
        ->name('leave-requests.decide');
    Route::resource('departments', DepartmentController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('positions', PositionController::class)
        ->only(['store', 'update', 'destroy']);
    Route::resource('shifts', ShiftController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('payroll-components', PayrollComponentController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('payroll-periods', PayrollPeriodController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('payroll-runs', PayrollRunController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('payroll-runs/{payrollRun}/process', [PayrollRunController::class, 'process'])
        ->name('payroll-runs.process');
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
    Route::resource('bank-files', BankFileController::class)
        ->only(['index', 'store', 'destroy']);
    Route::resource('reimbursements', ReimbursementController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::patch('reimbursements/{reimbursement}/decide', [ReimbursementController::class, 'decide'])
        ->name('reimbursements.decide');
    Route::resource('employee-loans', EmployeeLoanController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::patch('employee-loans/{employeeLoan}/decide', [EmployeeLoanController::class, 'decide'])
        ->name('employee-loans.decide');
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
    Route::patch('benefit-claims/{benefitClaim}/decide', [EmployeeBenefitController::class, 'decideClaim'])
        ->name('employee-benefits.claims.decide');
    Route::delete('benefit-claims/{benefitClaim}', [EmployeeBenefitController::class, 'destroyClaim'])
        ->name('employee-benefits.claims.destroy');

    Route::resource('work-visits', WorkVisitController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::patch('work-visits/{workVisit}/decide', [WorkVisitController::class, 'decide'])
        ->name('work-visits.decide');
    Route::post('work-visits/{workVisit}/reports', [WorkVisitController::class, 'storeReport'])
        ->name('work-visits.reports.store');
    Route::delete('work-visits/{workVisit}/reports/{report}', [WorkVisitController::class, 'destroyReport'])
        ->name('work-visits.reports.destroy');

    Route::resource('overtime-requests', OvertimeRequestController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::patch('overtime-requests/{overtimeRequest}/decide', [OvertimeRequestController::class, 'decide'])
        ->name('overtime-requests.decide');

    Route::resource('employee-documents', EmployeeDocumentController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('lifecycle', EmployeeLifecycleEventController::class)
        ->only(['index', 'store', 'destroy']);
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
    Route::resource('approval-flows', ApprovalFlowController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::resource('custom-fields', CustomFieldController::class)
        ->only(['index', 'store', 'update', 'destroy']);
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
    Route::get('report-builder', [ReportBuilderController::class, 'index'])->name('report-builder.index');
    Route::post('report-builder', [ReportBuilderController::class, 'store'])->name('report-builder.store');
    Route::get('report-builder/{reportDefinition}/run', [ReportBuilderController::class, 'run'])
        ->name('report-builder.run');
    Route::delete('report-builder/{reportDefinition}', [ReportBuilderController::class, 'destroy'])
        ->name('report-builder.destroy');

    // HR helpdesk / ticketing.
    Route::get('hr-tickets', [HrTicketController::class, 'index'])->name('hr-tickets.index');
    Route::post('hr-tickets', [HrTicketController::class, 'store'])->name('hr-tickets.store');
    Route::get('hr-tickets/{ticket}', [HrTicketController::class, 'show'])->name('hr-tickets.show');
    Route::post('hr-tickets/{ticket}/messages', [HrTicketController::class, 'reply'])
        ->name('hr-tickets.reply');
    Route::patch('hr-tickets/{ticket}', [HrTicketController::class, 'update'])->name('hr-tickets.update');

    // Time & Attendance (M02) — HR/admin web. Employee clock-in is mobile (Flutter).
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance-corrections', [AttendanceCorrectionController::class, 'index'])
        ->name('attendance-corrections.index');
    Route::patch('attendance-corrections/{attendanceCorrection}/decide', [AttendanceCorrectionController::class, 'decide'])
        ->name('attendance-corrections.decide');
    Route::resource('timesheets', TimesheetController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // Platform admin (super-admin only, cross-tenant).
    Route::middleware(EnsureSuperAdmin::class)
        ->prefix('platform')
        ->name('platform.')
        ->group(function () {
            Route::resource('tenants', TenantController::class);
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
            Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
            Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
            Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])
                ->name('backups.restore');
        });
});

require __DIR__.'/settings.php';
