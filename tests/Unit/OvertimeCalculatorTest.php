<?php

use App\Support\Payroll\OvertimeCalculator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->calc = new OvertimeCalculator;
    // Monthly base 9,750,000 → hourly = 9,750,000 / 173 = 56,358.9595...
    $this->base = 9_750_000;
});

it('derives the hourly rate as monthly base over 173', function () {
    expect($this->calc->hourlyRate($this->base))->toBe(9_750_000 / 173);
});

it('pays the first overtime hour at 1.5x', function () {
    // 1.5 * 56,358.9595 = 84,538.439 → 84,538
    expect($this->calc->payForMinutes($this->base, 60))->toBe(84_538);
});

it('pays the second overtime hour at 2x (tiered)', function () {
    // 2 hours: 1.5*1h + 2.0*1h = 3.5 * 56,358.3815 = 197,254.335 → 197,254
    expect($this->calc->payForMinutes($this->base, 120))->toBe(197_254);
});

it('handles fractional overtime hours', function () {
    // 1.5 hours: 1.5*1h + 2.0*0.5h = 2.5 * 56,358.3815 = 140,895.95 → 140,896
    expect($this->calc->payForMinutes($this->base, 90))->toBe(140_896);
});

it('returns zero for non-positive minutes or base', function () {
    expect($this->calc->payForMinutes($this->base, 0))->toBe(0)
        ->and($this->calc->payForMinutes(0, 120))->toBe(0)
        ->and($this->calc->hourlyRate(0))->toBe(0.0);
});

it('applies the tiered multiplier per occurrence, not on the lump sum', function () {
    // Two separate 1-hour occurrences = 2 * 84,538 = 169,076.
    // A single 2-hour occurrence would be 197,254 — proving per-day tiering.
    expect($this->calc->totalPay($this->base, [60, 60]))->toBe(169_076)
        ->and($this->calc->totalPay($this->base, [120]))->toBe(197_254);
});

it('applies holiday (rest-day) tiers 2x/3x/4x', function () {
    // 8h holiday: 8h @ 2x = 16 * 56,358.3815 = 901,734.
    expect($this->calc->payForMinutes($this->base, 480, 'holiday'))->toBe(901_734)
        // 10h holiday: 8h@2x (16) + 1h@3x (3) + 1h@4x (4) = 23 * 56,358.3815 = 1,296,243.
        ->and($this->calc->payForMinutes($this->base, 600, 'holiday'))->toBe(1_296_243)
        // Same minutes on a workday is cheaper than on a holiday.
        ->and($this->calc->payForMinutes($this->base, 480, 'workday'))
        ->toBeLessThan($this->calc->payForMinutes($this->base, 480, 'holiday'));
});

it('reads day_type from occurrence arrays in totalPay', function () {
    expect($this->calc->totalPay($this->base, [['minutes' => 480, 'day_type' => 'holiday']]))->toBe(901_734)
        ->and($this->calc->totalPay($this->base, [['minutes' => 120, 'day_type' => 'workday']]))->toBe(197_254);
});
