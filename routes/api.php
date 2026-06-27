<?php

use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BenefitController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OvertimeController;
use App\Http\Controllers\Api\V1\PayslipController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReimbursementController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\WorkVisitController;
use App\Http\Middleware\SetCurrentTenant;
use Illuminate\Support\Facades\Route;

/*
| Mobile API (Flutter ESS/MSS) — stateless JWT. Web stays on Fortify sessions.
| All paths are prefixed with /api/v1.
*/

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])->name('api.auth.login');

    Route::middleware(['auth:api', SetCurrentTenant::class])->group(function (): void {
        // Auth session
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::post('auth/refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');
        Route::get('auth/me', [AuthController::class, 'me'])->name('api.auth.me');

        // ESS — self data only
        Route::get('me/profile', [ProfileController::class, 'show'])->name('api.me.profile');
        Route::put('me/profile', [ProfileController::class, 'update'])->name('api.me.profile.update');

        // Device registration (FCM push + biometric flag)
        Route::post('me/devices', [DeviceController::class, 'register'])->name('api.me.devices.register');

        Route::get('me/payslips', [PayslipController::class, 'index'])->name('api.me.payslips');
        Route::get('me/payslips/{payslip}', [PayslipController::class, 'show'])->name('api.me.payslips.show');

        Route::get('me/leave/balances', [LeaveController::class, 'balances'])->name('api.me.leave.balances');
        Route::get('me/leave/requests', [LeaveController::class, 'requests'])->name('api.me.leave.requests');

        // Attendance — mobile clock-in/out (GPS + face), offline-capable
        Route::post('me/attendance/clock', [AttendanceController::class, 'clock'])->name('api.me.attendance.clock');
        Route::get('me/attendance/today', [AttendanceController::class, 'today'])->name('api.me.attendance.today');
        Route::get('me/attendance', [AttendanceController::class, 'index'])->name('api.me.attendance');

        // ESS write + status lists — submitted through the approval engine
        Route::post('me/leave-requests', [LeaveRequestController::class, 'store'])->name('api.me.leave-requests.store');

        Route::get('me/overtime-requests', [OvertimeController::class, 'index'])->name('api.me.overtime-requests');
        Route::post('me/overtime-requests', [OvertimeController::class, 'store'])->name('api.me.overtime-requests.store');

        Route::get('me/reimbursements', [ReimbursementController::class, 'index'])->name('api.me.reimbursements');
        Route::post('me/reimbursements', [ReimbursementController::class, 'store'])->name('api.me.reimbursements.store');

        Route::get('me/loans', [LoanController::class, 'index'])->name('api.me.loans');
        Route::post('me/loans', [LoanController::class, 'store'])->name('api.me.loans.store');

        Route::get('me/work-visits', [WorkVisitController::class, 'index'])->name('api.me.work-visits');
        Route::post('me/work-visits', [WorkVisitController::class, 'store'])->name('api.me.work-visits.store');

        Route::get('me/benefits', [BenefitController::class, 'index'])->name('api.me.benefits');
        Route::post('me/benefits/{employeeBenefit}/claims', [BenefitController::class, 'storeClaim'])->name('api.me.benefits.claims.store');

        // MSS — manager self-service
        Route::get('mss/approvals', [ApprovalController::class, 'index'])->name('api.mss.approvals');
        Route::post('mss/approvals/bulk', [ApprovalController::class, 'bulk'])->name('api.mss.approvals.bulk');
        Route::post('mss/approvals/{approvalRequest}/act', [ApprovalController::class, 'act'])->name('api.mss.approvals.act');
        Route::get('mss/team', [TeamController::class, 'index'])->name('api.mss.team');

        Route::get('me/notifications', [NotificationController::class, 'index'])->name('api.me.notifications');
        Route::post('me/notifications/read-all', [NotificationController::class, 'readAll'])->name('api.me.notifications.read-all');
        Route::post('me/notifications/{notification}/read', [NotificationController::class, 'read'])->name('api.me.notifications.read');
    });
});
