<?php

namespace App\Support;

class Features
{
    /**
     * @return list<array{key: string, label: string}>
     */
    public static function catalog(): array
    {
        return config('features.catalog', []);
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::catalog(), 'key');
    }

    /**
     * Build a feature_flags map ({key: bool}) from a list of enabled keys.
     *
     * @param  list<string>  $enabled
     * @return array<string, bool>
     */
    public static function flagsFrom(array $enabled): array
    {
        return collect(self::keys())
            ->mapWithKeys(fn (string $key): array => [$key => in_array($key, $enabled, true)])
            ->all();
    }

    /**
     * Enabled feature keys from a feature_flags map. Empty/missing = all enabled.
     *
     * @param  array<string, bool>|null  $flags
     * @return list<string>
     */
    public static function enabledFrom(?array $flags): array
    {
        if (empty($flags)) {
            return self::keys();
        }

        return array_values(array_filter(self::keys(), fn (string $key): bool => (bool) ($flags[$key] ?? false)));
    }
}
