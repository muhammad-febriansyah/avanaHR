<?php

namespace App\Support;

/**
 * Masks sensitive identifiers (NIK, NPWP, bank account) so only the last few
 * characters remain visible to users without the employee.view_sensitive
 * permission.
 */
class SensitiveData
{
    public static function mask(?string $value, int $visible = 4): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $length = mb_strlen($value);

        if ($length <= $visible) {
            return str_repeat('•', $length);
        }

        return str_repeat('•', $length - $visible).mb_substr($value, -$visible);
    }
}
