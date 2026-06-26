<?php

namespace App\Support\Payroll;

use RuntimeException;

/**
 * Computes annual PPh 21 (UU HPP) for the December year-end correction and the
 * 1721-A1 form: taxable income (PKP) = annual gross − occupational cost (biaya
 * jabatan) − deductible contributions − PTKP, rounded down to the nearest
 * thousand; tax is the progressive Pasal 17 rate applied to PKP.
 *
 * Rates/PTKP are PLACEHOLDER config (config/payroll.php) — validate against the
 * regulation before production use.
 */
class Pph21AnnualCalculator
{
    /**
     * Occupational cost (biaya jabatan): a fixed % of gross, capped annually.
     */
    public function occupationalCost(int $annualGross): int
    {
        if ($annualGross <= 0) {
            return 0;
        }

        $rate = (float) config('payroll.annual.occupational_cost_rate', 0.05);
        $cap = (int) config('payroll.annual.occupational_cost_cap', 6_000_000);

        return min((int) round($annualGross * $rate), $cap);
    }

    /**
     * Annual PTKP for a status.
     */
    public function ptkp(string $ptkpStatus): int
    {
        $ptkp = config("payroll.annual.ptkp_annual.{$ptkpStatus}");

        if ($ptkp === null) {
            throw new RuntimeException("PTKP tahunan tidak dikenal: {$ptkpStatus}.");
        }

        return (int) $ptkp;
    }

    /**
     * Taxable income (PKP) — never negative, rounded down to the nearest 1.000.
     */
    public function taxableIncome(int $annualGross, string $ptkpStatus, int $deductibleContributions = 0): int
    {
        $net = $annualGross
            - $this->occupationalCost($annualGross)
            - max(0, $deductibleContributions)
            - $this->ptkp($ptkpStatus);

        if ($net <= 0) {
            return 0;
        }

        return intdiv($net, 1000) * 1000;
    }

    /**
     * Annual PPh 21 for the given gross, PTKP status and deductible contributions.
     */
    public function annualTax(int $annualGross, string $ptkpStatus, int $deductibleContributions = 0): int
    {
        return $this->progressiveTax(
            $this->taxableIncome($annualGross, $ptkpStatus, $deductibleContributions),
        );
    }

    /**
     * Progressive Pasal 17 tax on a taxable income (PKP).
     */
    public function progressiveTax(int $taxableIncome): int
    {
        if ($taxableIncome <= 0) {
            return 0;
        }

        /** @var list<array{0:int|null,1:float}> $brackets */
        $brackets = config('payroll.annual.pasal17', []);

        if ($brackets === []) {
            throw new RuntimeException('Tarif Pasal 17 belum dikonfigurasi (config/payroll.php).');
        }

        $tax = 0.0;
        $lower = 0;

        foreach ($brackets as [$upper, $rate]) {
            if ($taxableIncome <= $lower) {
                break;
            }

            $ceiling = $upper ?? $taxableIncome;
            $portion = min($taxableIncome, $ceiling) - $lower;
            $tax += $portion * $rate;
            $lower = $ceiling;
        }

        return (int) round($tax);
    }
}
