<?php

namespace App\Support\Payroll;

/**
 * Computes overtime pay (uang lembur) per Kepmenaker 102/2004 for normal
 * workdays: hourly wage = monthly wage / 173; the first overtime hour is paid
 * at 1.5x and every subsequent hour at 2x.
 *
 * Rates are configurable in config/payroll.php. Rest-day / public-holiday
 * tiers (2x/3x/4x) are NOT yet applied — they require a day-type marker on the
 * overtime request.
 */
class OvertimeCalculator
{
    private int $divisor;

    private float $firstHourMultiplier;

    private float $subsequentMultiplier;

    public function __construct()
    {
        $this->divisor = (int) config('payroll.overtime.hours_divisor', 173);
        $this->firstHourMultiplier = (float) config('payroll.overtime.workday_first_hour_multiplier', 1.5);
        $this->subsequentMultiplier = (float) config('payroll.overtime.workday_subsequent_multiplier', 2.0);
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
     * Overtime pay (rupiah) for a single overtime occurrence of $minutes,
     * applying the tiered workday multipliers.
     */
    public function payForMinutes(int $monthlyBase, int $minutes): int
    {
        if ($minutes <= 0) {
            return 0;
        }

        $hours = $minutes / 60;
        $firstHour = min($hours, 1.0);
        $subsequentHours = max($hours - 1.0, 0.0);

        $weightedHours = ($firstHour * $this->firstHourMultiplier)
            + ($subsequentHours * $this->subsequentMultiplier);

        return (int) round($this->hourlyRate($monthlyBase) * $weightedHours);
    }

    /**
     * Total overtime pay (rupiah) across multiple occurrences. The tiered
     * multiplier is applied per occurrence (per overtime request/day), not to
     * the lump-sum of minutes.
     *
     * @param  iterable<int>  $minutesPerOccurrence
     */
    public function totalPay(int $monthlyBase, iterable $minutesPerOccurrence): int
    {
        $total = 0;
        foreach ($minutesPerOccurrence as $minutes) {
            $total += $this->payForMinutes($monthlyBase, (int) $minutes);
        }

        return $total;
    }
}
