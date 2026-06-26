<?php

use App\Support\Payroll\Pph21AnnualCalculator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->calc = new Pph21AnnualCalculator;
});

it('caps the occupational cost at the annual maximum', function () {
    // 5% of 120jt = 6jt = cap.
    expect($this->calc->occupationalCost(120_000_000))->toBe(6_000_000)
        // 5% of 80jt = 4jt < cap.
        ->and($this->calc->occupationalCost(80_000_000))->toBe(4_000_000)
        ->and($this->calc->occupationalCost(0))->toBe(0);
});

it('computes PKP rounded down to the nearest thousand, never negative', function () {
    // gross 120jt − biaya jabatan 6jt − PTKP 54jt = 60jt.
    expect($this->calc->taxableIncome(120_000_000, 'TK/0'))->toBe(60_000_000)
        // below PTKP → 0.
        ->and($this->calc->taxableIncome(40_000_000, 'TK/0'))->toBe(0);
});

it('applies the progressive Pasal 17 brackets', function () {
    // PKP 60jt → all at 5% = 3jt.
    expect($this->calc->progressiveTax(60_000_000))->toBe(3_000_000)
        // PKP 240jt → 60jt@5% (3jt) + 180jt@15% (27jt) = 30jt.
        ->and($this->calc->progressiveTax(240_000_000))->toBe(30_000_000)
        ->and($this->calc->progressiveTax(0))->toBe(0);
});

it('computes annual tax end-to-end', function () {
    // gross 120jt TK/0 → PKP 60jt → 3jt.
    expect($this->calc->annualTax(120_000_000, 'TK/0'))->toBe(3_000_000)
        // gross 300jt TK/0 → biaya jabatan 6jt → PKP 240jt → 30jt.
        ->and($this->calc->annualTax(300_000_000, 'TK/0'))->toBe(30_000_000);
});

it('deducts pension contributions before PTKP', function () {
    // gross 120jt − biaya jabatan 6jt − pension 2jt − PTKP 54jt = 58jt → 58jt@5% = 2.9jt.
    expect($this->calc->annualTax(120_000_000, 'TK/0', 2_000_000))->toBe(2_900_000);
});

it('throws for an unknown PTKP status', function () {
    $this->calc->ptkp('ZZ/9');
})->throws(RuntimeException::class);
