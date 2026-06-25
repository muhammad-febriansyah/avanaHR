<?php

namespace App\Console\Commands;

use App\Actions\Employee\ApplyMovementAction;
use App\Enums\EmployeeMovementStatus;
use App\Models\EmployeeMovement;
use App\Models\Scopes\TenantScope;
use App\Support\CurrentTenant;
use Illuminate\Console\Command;

class ApplyDueMovements extends Command
{
    protected $signature = 'movements:apply-due';

    protected $description = 'Apply scheduled employee movements whose effective date has arrived (clearance permitting).';

    public function handle(ApplyMovementAction $action, CurrentTenant $currentTenant): int
    {
        $due = EmployeeMovement::query()
            ->withoutGlobalScope(TenantScope::class)
            ->with('tenant')
            ->where('status', EmployeeMovementStatus::Scheduled)
            ->whereDate('effective_date', '<=', now())
            ->orderBy('tenant_id')
            ->get();

        $applied = 0;
        $skipped = 0;

        foreach ($due as $movement) {
            $currentTenant->set($movement->tenant);

            if ($movement->hasPendingClearance()) {
                $skipped++;
                $this->warn("Skip movement #{$movement->id}: clearance belum selesai.");

                continue;
            }

            $action->execute($movement);
            $applied++;
        }

        $currentTenant->forget();

        $this->info("Movement terjadwal diterapkan: {$applied}, dilewati: {$skipped}.");

        return self::SUCCESS;
    }
}
