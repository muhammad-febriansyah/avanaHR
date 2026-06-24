<?php

use App\Http\Controllers\BankFileController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeLoanController;
use App\Http\Controllers\HolidayController;
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
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ThrBonusRunController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkCalendarController;
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
        ->only(['index', 'store', 'update', 'destroy']);
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
    Route::resource('overtime-requests', OvertimeRequestController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::patch('overtime-requests/{overtimeRequest}/decide', [OvertimeRequestController::class, 'decide'])
        ->name('overtime-requests.decide');

    // Platform admin (super-admin only, cross-tenant).
    Route::middleware(EnsureSuperAdmin::class)
        ->prefix('platform')
        ->name('platform.')
        ->group(function () {
            Route::resource('tenants', TenantController::class);
        });
});

require __DIR__.'/settings.php';
