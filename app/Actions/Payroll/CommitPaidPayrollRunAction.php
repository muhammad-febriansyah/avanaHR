<?php

namespace App\Actions\Payroll;

use App\Models\EmployeeLoan;
use App\Models\PayrollRun;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\DB;

/**
 * Commits the monetary side-effects that a payroll run only previewed while it
 * was being calculated: loan installments become paid (reducing the loan
 * balance) and payroll-settled reimbursements are stamped with the run's
 * period so they are not paid again. Driven entirely by each payslip's
 * snapshot so what is committed matches exactly what was shown on the slip.
 *
 * Idempotent: installment rows are keyed by (loan, period) and reimbursements
 * are only stamped while their period is still null, so re-invocation is a
 * no-op.
 */
class CommitPaidPayrollRunAction
{
    public function execute(PayrollRun $run): void
    {
        DB::transaction(function () use ($run): void {
            foreach ($run->payslips()->get(['id', 'snapshot', 'tenant_id']) as $payslip) {
                $snapshot = $payslip->snapshot ?? [];

                foreach ($snapshot['loan_deductions'] ?? [] as $deduction) {
                    $this->commitLoanInstallment(
                        (int) $deduction['loan_id'],
                        (int) $deduction['amount'],
                        (int) $run->period_id,
                    );
                }

                foreach ($snapshot['reimbursement_ids'] ?? [] as $reimbursementId) {
                    Reimbursement::query()
                        ->whereKey($reimbursementId)
                        ->whereNull('period_id')
                        ->update(['period_id' => $run->period_id]);
                }
            }
        });
    }

    private function commitLoanInstallment(int $loanId, int $amount, int $periodId): void
    {
        $loan = EmployeeLoan::query()->find($loanId);
        if ($loan === null) {
            return;
        }

        $installment = $loan->installments()->firstOrCreate(
            ['period_id' => $periodId],
            ['tenant_id' => $loan->tenant_id, 'amount' => $amount, 'status' => 'paid'],
        );

        // Only reduce the balance the first time this period is committed.
        if ($installment->wasRecentlyCreated) {
            $loan->decrement('outstanding', min($amount, (int) $loan->outstanding));
        }
    }
}
