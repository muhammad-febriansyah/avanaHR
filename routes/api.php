<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OvertimeController;
use App\Http\Controllers\Api\V1\PayslipController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReimbursementController;
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

        Route::get('me/payslips', [PayslipController::class, 'index'])->name('api.me.payslips');
        Route::get('me/payslips/{payslip}', [PayslipController::class, 'show'])->name('api.me.payslips.show');

        Route::get('me/leave/balances', [LeaveController::class, 'balances'])->name('api.me.leave.balances');
        Route::get('me/leave/requests', [LeaveController::class, 'requests'])->name('api.me.leave.requests');

        // Attendance — mobile clock-in/out (GPS + face), offline-capable
        Route::post('me/attendance/clock', [AttendanceController::class, 'clock'])->name('api.me.attendance.clock');
        Route::get('me/attendance/today', [AttendanceController::class, 'today'])->name('api.me.attendance.today');
        Route::get('me/attendance', [AttendanceController::class, 'index'])->name('api.me.attendance');

        // ESS write — submitted through the approval engine
        Route::post('me/leave-requests', [LeaveRequestController::class, 'store'])->name('api.me.leave-requests.store');
        Route::post('me/overtime-requests', [OvertimeController::class, 'store'])->name('api.me.overtime-requests.store');
        Route::post('me/reimbursements', [ReimbursementController::class, 'store'])->name('api.me.reimbursements.store');

        Route::get('me/notifications', [NotificationController::class, 'index'])->name('api.me.notifications');
        Route::post('me/notifications/read-all', [NotificationController::class, 'readAll'])->name('api.me.notifications.read-all');
        Route::post('me/notifications/{notification}/read', [NotificationController::class, 'read'])->name('api.me.notifications.read');
    });
});
