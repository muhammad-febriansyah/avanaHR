<?php

namespace App\Support\Payroll;

use App\Models\BpjsParameter;
use App\Models\EmployeeBpjsProfile;

/**
 * Computes monthly BPJS contributions (Kesehatan + Ketenagakerjaan) split
 * into employee-paid (deducted from pay) and employer-paid portions.
 *
 * Rates come from `bpjs_parameters` (per-tenant, effective-dated); bases and
 * participation come from `employee_bpjs_profiles`. All amounts in rupiah.
 */
class BpjsCalculator
{
    /**
     * @return array{
     *     kesehatan_employee:int, kesehatan_employer:int,
     *     jht_employee:int, jht_employer:int,
     *     jkk:int, jkm:int,
     *     jp_employee:int, jp_employer:int,
     *     employee_total:int, employer_total:int
     * }
     */
    public function compute(BpjsParameter $param, ?EmployeeBpjsProfile $profile): array
    {
        $zero = [
            'kesehatan_employee' => 0, 'kesehatan_employer' => 0,
            'jht_employee' => 0, 'jht_employer' => 0,
            'jkk' => 0, 'jkm' => 0,
            'jp_employee' => 0, 'jp_employer' => 0,
            'employee_total' => 0, 'employer_total' => 0,
        ];

        if ($profile === null) {
            return $zero;
        }

        $flags = $profile->participation_flags ?? [];
        $tk = $param->tk_rates ?? [];
        $result = $zero;

        // BPJS Kesehatan — basis di-cap.
        if ($flags['kesehatan'] ?? false) {
            $kesBase = min((int) $profile->kesehatan_basis, (int) $param->kes_cap);
            $result['kesehatan_employee'] = $this->pct($kesBase, (float) $param->kes_rate_employee);
            $result['kesehatan_employer'] = $this->pct($kesBase, (float) $param->kes_rate_employer);
        }

        $tkBase = (int) $profile->tk_basis;

        if ($flags['jht'] ?? false) {
            $result['jht_employee'] = $this->pct($tkBase, (float) ($tk['jht_employee'] ?? 0));
            $result['jht_employer'] = $this->pct($tkBase, (float) ($tk['jht_employer'] ?? 0));
        }

        if ($flags['jkk'] ?? false) {
            $result['jkk'] = $this->pct($tkBase, (float) ($tk['jkk'] ?? 0));
        }

        if ($flags['jkm'] ?? false) {
            $result['jkm'] = $this->pct($tkBase, (float) ($tk['jkm'] ?? 0));
        }

        if ($flags['jp'] ?? false) {
            $jpBase = min($tkBase, (int) ($tk['jp_cap'] ?? $tkBase));
            $result['jp_employee'] = $this->pct($jpBase, (float) ($tk['jp_employee'] ?? 0));
            $result['jp_employer'] = $this->pct($jpBase, (float) ($tk['jp_employer'] ?? 0));
        }

        $result['employee_total'] = $result['kesehatan_employee'] + $result['jht_employee'] + $result['jp_employee'];
        $result['employer_total'] = $result['kesehatan_employer'] + $result['jht_employer']
            + $result['jkk'] + $result['jkm'] + $result['jp_employer'];

        return $result;
    }

    private function pct(int $base, float $ratePercent): int
    {
        return (int) round($base * $ratePercent / 100);
    }
}
