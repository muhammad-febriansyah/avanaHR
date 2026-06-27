<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Global, platform-wide web/site settings (AvanaHR identity): name, SEO meta,
 * logo, favicon, contact, and social links. Stored as a single JSON row in the
 * global `settings` table (key = web) and cached for cheap per-request reads.
 */
class SiteSettings
{
    public const KEY = 'web';

    private const CACHE_KEY = 'site_settings_web';

    /**
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'site_name' => 'AvanaHR',
        'tagline' => 'Advancing People, Empowering Growth',
        'meta_keywords' => '',
        'meta_description' => '',
        'logo_path' => null,
        'favicon_path' => null,
        'contact_email' => '',
        'contact_phone' => '',
        'address' => '',
        'social' => [
            'facebook' => '',
            'instagram' => '',
            'twitter' => '',
            'linkedin' => '',
            'youtube' => '',
            'tiktok' => '',
        ],
    ];

    /**
     * Stored settings merged over the defaults.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $stored = Setting::query()->where('key', self::KEY)->value('value');
            $merged = array_merge(self::DEFAULTS, is_array($stored) ? $stored : []);
            $merged['social'] = array_merge(
                self::DEFAULTS['social'],
                is_array($merged['social'] ?? null) ? $merged['social'] : [],
            );

            return $merged;
        });
    }

    /**
     * Settings enriched with resolved public URLs for logo and favicon.
     *
     * @return array<string, mixed>
     */
    public static function forView(): array
    {
        $settings = self::all();

        return [
            ...$settings,
            'logo_url' => $settings['logo_path'] ? Storage::url($settings['logo_path']) : null,
            'favicon_url' => $settings['favicon_path'] ? Storage::url($settings['favicon_path']) : null,
        ];
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
