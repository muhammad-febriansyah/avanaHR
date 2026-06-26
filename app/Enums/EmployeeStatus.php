<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Probation = 'probation';
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Suspended = 'suspended';
    case Resigned = 'resigned';
    case Terminated = 'terminated';

    /** Bahasa Indonesia label shown in the UI. */
    public function label(): string
    {
        return match ($this) {
            self::Probation => 'Masa Percobaan',
            self::Active => 'Aktif',
            self::OnLeave => 'Cuti',
            self::Suspended => 'Diskors',
            self::Resigned => 'Mengundurkan Diri',
            self::Terminated => 'Diberhentikan',
        };
    }
}
