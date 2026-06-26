<?php

namespace App\Support\Security;

use App\Models\TenantSetting;
use App\Support\CurrentTenant;
use Illuminate\Validation\Rules\Password;

/**
 * Builds the password complexity rule from the current tenant's stored
 * security policy (tenant_settings key 'security'), merged over sane defaults.
 *
 * When no tenant is bound to the request (e.g. platform-level or unauthenticated
 * context) it falls back to a minimal 8-character rule so auth flows keep working.
 */
class PasswordPolicy
{
    private const KEY = 'security';

    /**
     * Default tenant security policy, mirrored from SecuritySettingController.
     *
     * @var array<string, int|bool>
     */
    private const DEFAULTS = [
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_number' => true,
        'password_require_symbol' => false,
    ];

    /**
     * Build the Password validation rule for the current tenant.
     */
    public static function rules(): Password
    {
        if (! app(CurrentTenant::class)->check()) {
            return Password::min(8);
        }

        $config = self::config();

        $rule = Password::min((int) $config['password_min_length']);

        if ($config['password_require_uppercase']) {
            $rule->mixedCase();
        }

        if ($config['password_require_number']) {
            $rule->numbers();
        }

        if ($config['password_require_symbol']) {
            $rule->symbols();
        }

        return $rule;
    }

    /**
     * Resolve the current tenant's security config merged over defaults.
     *
     * @return array<string, int|bool>
     */
    private static function config(): array
    {
        $stored = TenantSetting::query()->where('key', self::KEY)->value('value') ?? [];

        return array_merge(self::DEFAULTS, is_array($stored) ? $stored : []);
    }
}
