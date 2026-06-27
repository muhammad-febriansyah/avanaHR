<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PayslipController;
use App\Http\Controllers\Api\V1\ProfileController;
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

        Route::get('me/notifications', [NotificationController::class, 'index'])->name('api.me.notifications');
        Route::post('me/notifications/read-all', [NotificationController::class, 'readAll'])->name('api.me.notifications.read-all');
        Route::post('me/notifications/{notification}/read', [NotificationController::class, 'read'])->name('api.me.notifications.read');
    });
});
