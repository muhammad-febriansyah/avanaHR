<?php

namespace App\Http\Middleware;

use App\Models\TenantSetting;
use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the current tenant's runtime security policy on authenticated web
 * requests: idle session timeout and (optionally) mandatory 2FA enrolment.
 *
 * No-ops when there is no tenant context, the user is a guest, or the relevant
 * policy is disabled — so default settings (timeout 120, enforce_2fa false)
 * never change existing behaviour.
 */
class EnforceSessionTimeout
{
    private const KEY = 'security';

    /**
     * Default policy, mirrored from SecuritySettingController.
     *
     * @var array<string, int|bool>
     */
    private const DEFAULTS = [
        'session_timeout_minutes' => 120,
        'enforce_2fa' => false,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! app(CurrentTenant::class)->check()) {
            return $next($request);
        }

        $config = $this->config();

        if (($response = $this->enforceIdleTimeout($request, $config)) !== null) {
            return $response;
        }

        if (($response = $this->enforceTwoFactor($request, $user, $config)) !== null) {
            return $response;
        }

        return $next($request);
    }

    /**
     * Log out and redirect to login when the session has been idle longer than
     * the tenant's configured timeout. Otherwise stamp the last activity time.
     *
     * @param  array<string, int|bool>  $config
     */
    private function enforceIdleTimeout(Request $request, array $config): ?Response
    {
        $timeout = (int) $config['session_timeout_minutes'];

        if ($timeout <= 0) {
            return null;
        }

        $lastActivity = $request->session()->get('last_activity');

        if ($lastActivity !== null && (now()->timestamp - (int) $lastActivity) > ($timeout * 60)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan masuk kembali.');
        }

        $request->session()->put('last_activity', now()->timestamp);

        return null;
    }

    /**
     * Redirect users who have not enrolled in 2FA to the security settings page
     * when the tenant requires 2FA. No-op when disabled (the default).
     *
     * @param  array<string, int|bool>  $config
     */
    private function enforceTwoFactor(Request $request, mixed $user, array $config): ?Response
    {
        if (! $config['enforce_2fa']) {
            return null;
        }

        if ($user->two_factor_secret !== null) {
            return null;
        }

        if ($this->isExemptFromTwoFactor($request)) {
            return null;
        }

        return redirect()->route('security.edit')
            ->with('status', 'Autentikasi dua faktor wajib diaktifkan sebelum melanjutkan.');
    }

    /**
     * Routes that must stay reachable so the user can actually enable 2FA or
     * sign out without being trapped in a redirect loop.
     */
    private function isExemptFromTwoFactor(Request $request): bool
    {
        return $request->is(
            'settings',
            'settings/*',
            'security-settings',
            'security-settings/*',
            'user/*',
            'two-factor-challenge',
            'logout',
            'login',
        );
    }

    /**
     * Resolve the current tenant's security config merged over defaults.
     *
     * @return array<string, int|bool>
     */
    private function config(): array
    {
        $stored = TenantSetting::query()->where('key', self::KEY)->value('value') ?? [];

        return array_merge(self::DEFAULTS, is_array($stored) ? $stored : []);
    }
}
