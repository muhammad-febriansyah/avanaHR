<?php

namespace App\Enums;

enum EmployeeMovementStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Applied = 'applied';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Terjadwal',
            self::Applied => 'Diterapkan',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
