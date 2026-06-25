<?php

namespace App\Enums;

enum EmployeeMovementType: string
{
    case Promotion = 'promotion';
    case Transfer = 'transfer';
    case Demotion = 'demotion';
    case ContractChange = 'contract_change';
    case Suspension = 'suspension';
    case Reactivate = 'reactivate';
    case Resign = 'resign';
    case Terminate = 'terminate';

    public function label(): string
    {
        return match ($this) {
            self::Promotion => 'Promosi',
            self::Transfer => 'Mutasi',
            self::Demotion => 'Demosi',
            self::ContractChange => 'Perubahan Kontrak',
            self::Suspension => 'Skorsing',
            self::Reactivate => 'Aktif Kembali',
            self::Resign => 'Resign',
            self::Terminate => 'Terminasi',
        };
    }

    /**
     * Exit movements that need a clearance checklist before final apply.
     */
    public function isExit(): bool
    {
        return in_array($this, [self::Resign, self::Terminate], true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type): array => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
