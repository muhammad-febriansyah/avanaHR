<?php

namespace App\Console\Commands;

use App\Approvals\ApprovalEngine;
use App\Approvals\ApprovalNotifier;
use App\Enums\ApprovalStatus;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStepState;
use App\Models\Scopes\TenantScope;
use App\Support\CurrentTenant;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

/**
 * Scans pending approval steps whose SLA deadline has passed: first sends a
 * reminder to the approver(s), then on a later run escalates to the step's
 * escalation target. Runs cross-tenant.
 */
class CheckApprovalSla extends Command
{
    protected $signature = 'approvals:check-sla';

    protected $description = 'Remind and escalate approval steps that have breached their SLA deadline.';

    public function handle(ApprovalEngine $engine, ApprovalNotifier $notifier, CurrentTenant $currentTenant): int
    {
        $pending = ApprovalRequest::query()
            ->withoutGlobalScope(TenantScope::class)
            ->with(['flow.steps', 'tenant'])
            ->where('status', ApprovalStatus::Pending->value)
            ->orderBy('tenant_id')
            ->get();

        $reminded = 0;
        $escalated = 0;

        foreach ($pending as $request) {
            $currentTenant->set($request->tenant);
            app(PermissionRegistrar::class)->setPermissionsTeamId($request->tenant_id);

            $step = $request->flow?->steps->firstWhere('order', $request->current_step_order);

            if ($step === null) {
                continue;
            }

            $state = ApprovalStepState::query()
                ->where('request_id', $request->id)
                ->where('step_order', $request->current_step_order)
                ->first();

            if ($state === null || $state->due_at === null || now()->lt($state->due_at)) {
                continue;
            }

            $payload = ['request_id' => $request->id, 'step_order' => $step->order];

            if ($state->reminded_at === null) {
                $state->reminded_at = now();
                $state->save();
                $notifier->toUsers((int) $request->tenant_id, $engine->approverUserIds($request, $step), 'approval.sla_reminder', $payload);
                $reminded++;

                continue;
            }

            if (! empty($step->escalate_to) && $state->escalated_at === null) {
                $state->status = 'escalated';
                $state->escalated_at = now();
                $state->save();
                $notifier->toUsers((int) $request->tenant_id, $engine->escalateTargetUserIds($step, (int) $request->tenant_id), 'approval.escalated', $payload);
                $escalated++;
            }
        }

        $currentTenant->forget();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $this->info("SLA approval — reminder: {$reminded}, eskalasi: {$escalated}.");

        return self::SUCCESS;
    }
}
