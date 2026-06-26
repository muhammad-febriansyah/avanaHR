<?php

namespace App\Support\Payroll;

/**
 * Computes overtime pay (uang lembur) per Kepmenaker 102/2004. Hourly wage =
 * monthly wage / 173, multiplied by tiered rates that depend on the day type:
 * a normal workday (1.5x first hour, 2x after) or a rest day / public holiday
 * (2x/3x/4x). Tiers are configurable in config/payroll.php.
 */
class OvertimeCalculator
{
    private int $divisor;

    public function __construct()
    {
        $this->divisor = (int) config('payroll.overtime.hours_divisor', 173);
    }

    /**
     * Hourly wage (rupiah, may be fractional) from the monthly wage base.
     */
    public function hourlyRate(int $monthlyBase): float
    {
        if ($monthlyBase <= 0 || $this->divisor <= 0) {
            return 0.0;
        }

        return $monthlyBase / $this->divisor;
    }

    /**
     * Overtime pay (rupiah) for a single occurrence of $minutes on the given
     * day type, applying the tiered multipliers progressively over the hours.
     */
    public function payForMinutes(int $monthlyBase, int $minutes, string $dayType = 'workday'): int
    {
        if ($minutes <= 0) {
            return 0;
        }

        $hours = $minutes / 60;
        $weightedHours = 0.0;
        $lower = 0.0;

        foreach ($this->tiers($dayType) as [$upTo, $multiplier]) {
            if ($hours <= $lower) {
                break;
            }

            $ceiling = $upTo ?? $hours;
            $portion = min($hours, (float) $ceiling) - $lower;
            $weightedHours += $portion * (float) $multiplier;
            $lower = (float) $ceiling;
        }

        return (int) round($this->hourlyRate($monthlyBase) * $weightedHours);
    }

    /**
     * Total overtime pay (rupiah) across multiple occurrences. The tiered
     * multiplier is applied per occurrence (per day), not to the lump sum.
     *
     * @param  iterable<array{minutes:int, day_type?:string}|int>  $occurrences
     */
    public function totalPay(int $monthlyBase, iterable $occurrences): int
    {
        $total = 0;
        foreach ($occurrences as $occurrence) {
            if (is_array($occurrence)) {
                $total += $this->payForMinutes($monthlyBase, (int) $occurrence['minutes'], $occurrence['day_type'] ?? 'workday');
            } else {
                $total += $this->payForMinutes($monthlyBase, (int) $occurrence);
            }
        }

        return $total;
    }

    /**
     * @return list<array{0:int|null,1:float}>
     */
    private function tiers(string $dayType): array
    {
        $tiers = config("payroll.overtime.tiers.{$dayType}")
            ?? config('payroll.overtime.tiers.workday', [[1, 1.5], [null, 2.0]]);

        return $tiers;
    }
}
