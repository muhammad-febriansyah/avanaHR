<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateSecuritySettingRequest;
use App\Models\TenantSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecuritySettingController extends Controller
{
    private const KEY = 'security';

    /**
     * Default tenant security policy, merged under any stored overrides.
     *
     * @var array<string, int|bool>
     */
    private const DEFAULTS = [
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_number' => true,
        'password_require_symbol' => false,
        'password_expiry_days' => 0,
        'session_timeout_minutes' => 120,
        'max_login_attempts' => 5,
        'lockout_minutes' => 15,
        'enforce_2fa' => false,
    ];

    public function edit(Request $request): Response
    {
        abort_unless($request->user()->can('setting.manage'), 403);

        return Inertia::render('security-settings/edit', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Keamanan', 'href' => route('security-settings.edit')],
            ],
            'settings' => $this->current(),
        ]);
    }

    public function update(UpdateSecuritySettingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $value = [
            'password_min_length' => (int) $data['password_min_length'],
            'password_require_uppercase' => $request->boolean('password_require_uppercase'),
            'password_require_number' => $request->boolean('password_require_number'),
            'password_require_symbol' => $request->boolean('password_require_symbol'),
            'password_expiry_days' => (int) $data['password_expiry_days'],
            'session_timeout_minutes' => (int) $data['session_timeout_minutes'],
            'max_login_attempts' => (int) $data['max_login_attempts'],
            'lockout_minutes' => (int) $data['lockout_minutes'],
            'enforce_2fa' => $request->boolean('enforce_2fa'),
        ];

        TenantSetting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => $value, 'type' => 'security'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kebijakan keamanan disimpan.']);

        return back();
    }

    /**
     * @return array<string, int|bool>
     */
    private function current(): array
    {
        $stored = TenantSetting::query()->where('key', self::KEY)->value('value') ?? [];

        return array_merge(self::DEFAULTS, is_array($stored) ? $stored : []);
    }
}
