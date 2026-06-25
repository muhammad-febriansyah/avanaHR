<?php

namespace App\Support\Payroll;

use RuntimeException;

/**
 * Computes monthly PPh 21 using the TER (Tarif Efektif Rata-rata) method
 * per PMK 168/2023. The effective rate is looked up from a configured table
 * (config/payroll.php) by PTKP category and monthly taxable gross.
 *
 * Tables are PLACEHOLDER data — validate against the regulation before use.
 */
class Pph21TerCalculator
{
    /**
     * Monthly PPh 21 (rupiah) for a given taxable gross and PTKP status.
     */
    public function monthlyTax(int $taxableGross, string $ptkpStatus): int
    {
        if ($taxableGross <= 0) {
            return 0;
        }

        return (int) round($taxableGross * $this->rate($taxableGross, $ptkpStatus));
    }

    /**
     * The effective TER rate (decimal fraction) for the amount + PTKP status.
     */
    public function rate(int $taxableGross, string $ptkpStatus): float
    {
        $category = config("payroll.ptkp_ter_category.{$ptkpStatus}");

        if ($category === null) {
            throw new RuntimeException("Status PTKP tidak dikenal: {$ptkpStatus}.");
        }

        /** @var list<array{0:int|null,1:float}> $brackets */
        $brackets = config("payroll.ter_monthly.{$category}", []);

        if ($brackets === []) {
            throw new RuntimeException("Tabel TER kategori {$category} belum dikonfigurasi (config/payroll.php).");
        }

        foreach ($brackets as [$upTo, $rate]) {
            if ($upTo === null || $taxableGross <= $upTo) {
                return $rate;
            }
        }

        // Fallback to the last bracket's rate (highest band).
        return $brackets[array_key_last($brackets)][1];
    }
}
