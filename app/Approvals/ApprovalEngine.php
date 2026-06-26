<?php

namespace App\Approvals;

use App\Contracts\Approvable;
use App\Enums\ApprovalActionType;
use App\Enums\ApprovalStatus;
use App\Models\ApprovalAction;
use App\Models\ApprovalDelegation;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStepState;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Generic, deterministic approval engine shared by every transaction module.
 *
 * Modules implement {@see Approvable} and call {@see submit()}; the engine
 * routes the request through the configured flow, tracks per-step state, and
 * executes the source-module callbacks on the final decision. When no active
 * flow exists for the transaction type the request is auto-approved.
 */
class ApprovalEngine
{
    public function __construct(private readonly ApprovalNotifier $notifier) {}

    /**
     * Route an approvable transaction. Returns the created request, or null
     * when auto-approved because no active flow is configured.
     *
     * @param  Approvable&Model  $model
     */
    public function submit(Approvable $model, ?User $requester = null): ?ApprovalRequest
    {
        if (! $model instanceof Model) {
            throw new ApprovalException('Approvable must be an Eloquent model.');
        }

        // Idempotent: never open a second pending request for the same record.
        if (method_exists($model, 'pendingApprovalRequest') && ($existing = $model->pendingApprovalRequest()) !== null) {
            return $existing;
        }

        $context = $model->approvalContext();
        $flow = $this->resolveFlow($model->approvalType(), $context, (int) $model->tenant_id);

        if ($flow === null || $flow->steps->isEmpty()) {
            $model->onApproved();

            return null;
        }

        return DB::transaction(function () use ($model, $flow, $context, $requester): ApprovalRequest {
            $firstOrder = (int) $flow->steps->min('order');

            $request = ApprovalRequest::create([
                'tenant_id' => $model->tenant_id,
                'approvable_type' => $model->getMorphClass(),
                'approvable_id' => $model->getKey(),
                'flow_id' => $flow->id,
                'requested_by' => $requester?->id ?? $model->approvalRequesterUserId(),
                'status' => ApprovalStatus::Pending,
                'current_step_order' => $firstOrder,
                'payload_snapshot' => $context,
                'submitted_at' => now(),
            ]);

            foreach ($flow->steps as $step) {
                ApprovalStepState::create([
                    'request_id' => $request->id,
                    'step_order' => $step->order,
                    'approver_id' => $this->resolveApprover($step, $model, $requester),
                    'status' => 'pending',
                ]);
            }

            if (method_exists($model, 'setActiveApprovalRequest')) {
                $model->setActiveApprovalRequest($request->id);
            }

            // Start the SLA clock on the first step and notify its approvers.
            $firstStep = $flow->steps->firstWhere('order', $firstOrder);
            $this->armSla($request, $firstStep);
            $this->notifyAssigned($request, $firstStep);

            return $request;
        });
    }

    /**
     * Apply an approver's decision to the current step and advance or finalize.
     *
     * @throws ApprovalException
     */
    public function act(ApprovalRequest $request, User $actor, ApprovalActionType $action, ?string $reason = null): void
    {
        if ($request->status !== ApprovalStatus::Pending) {
            throw new ApprovalException('Permintaan ini sudah selesai diproses.');
        }

        $flow = $request->flow;
        $step = $flow?->steps()->where('order', $request->current_step_order)->first();

        if ($step === null) {
            throw new ApprovalException('Langkah persetujuan tidak ditemukan.');
        }

        if (! $this->canActOnStep($request, $step, $actor)) {
            throw new ApprovalException('Anda bukan approver untuk langkah ini.');
        }

        // Segregation of duties: the requester may not approve their own request.
        if ($request->requested_by !== null && $request->requested_by === $actor->id) {
            throw new ApprovalException('Pemohon tidak boleh menyetujui pengajuannya sendiri.');
        }

        // When acting on behalf of another approver via delegation, the action
        // records the delegate while the step keeps the original approver.
        $note = $this->delegationNote($request, $step, $actor);

        DB::transaction(function () use ($request, $step, $actor, $action, $reason, $note): void {
            ApprovalAction::create([
                'request_id' => $request->id,
                'step_order' => $step->order,
                'actor_id' => $actor->id,
                'action' => $action->value,
                'reason' => $note ? trim($note.' '.($reason ?? '')) : $reason,
                'created_at' => now(),
            ]);

            $this->audit($request, $actor, 'approval.'.$action->value, $reason);

            if ($action === ApprovalActionType::Reject) {
                $this->markStep($request, $step, 'rejected', $actor, $reason);
                $this->finalize($request, ApprovalStatus::Rejected);

                return;
            }

            if ($action === ApprovalActionType::Revise) {
                $this->markStep($request, $step, 'skipped', $actor, $reason);
                $this->finalize($request, ApprovalStatus::Revision);

                return;
            }

            // Approve: a parallel step only advances once min_approvals distinct
            // approvers have signed off; until then it stays pending.
            if ($this->stepSatisfied($request, $step)) {
                $this->markStep($request, $step, 'approved', $actor, $reason);
                $this->advanceOrComplete($request);
            }
        });
    }

    /**
     * Whether the actor may act on this request's current step right now
     * (eligible approver, not the requester, request still pending).
     */
    public function canAct(ApprovalRequest $request, User $actor): bool
    {
        if ($request->status !== ApprovalStatus::Pending) {
            return false;
        }

        if ($request->requested_by !== null && $request->requested_by === $actor->id) {
            return false;
        }

        $step = $request->flow?->steps()->where('order', $request->current_step_order)->first();

        return $step !== null && $this->canActOnStep($request, $step, $actor);
    }

    /**
     * Pick the single active flow whose conditions match the context. Lowest id
     * wins so routing is deterministic and explainable to auditors.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolveFlow(string $type, array $context, int $tenantId): ?ApprovalFlow
    {
        return ApprovalFlow::query()
            ->where('tenant_id', $tenantId)
            ->where('transaction_type', $type)
            ->where('is_active', true)
            ->with(['steps' => fn ($query) => $query->orderBy('order')])
            ->orderBy('id')
            ->get()
            ->first(fn (ApprovalFlow $flow): bool => $this->conditionsMatch($flow->conditions, $context));
    }

    /**
     * Evaluate a flow's conditions against the transaction context. Null/empty
     * conditions always match. Supported keys: amount_min, amount_max,
     * department_id (scalar or list), grade_in (list), branch_id.
     *
     * @param  array<string, mixed>|null  $conditions
     * @param  array<string, mixed>  $context
     */
    public function conditionsMatch(?array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $key => $value) {
            $matches = match ($key) {
                'amount_min' => (float) ($context['amount'] ?? 0) >= (float) $value,
                'amount_max' => (float) ($context['amount'] ?? 0) <= (float) $value,
                'grade_in' => in_array($context['grade'] ?? null, (array) $value, true),
                'department_id' => in_array($context['department_id'] ?? null, (array) $value, false),
                'branch_id' => in_array($context['branch_id'] ?? null, (array) $value, false),
                default => true,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve a concrete approver user id for a step, or null when the step is
     * role-based (eligibility is evaluated dynamically at act time).
     *
     * @param  Approvable&Model  $model
     */
    private function resolveApprover(ApprovalFlowStep $step, Approvable $model, ?User $requester): ?int
    {
        return match ($step->approver_type) {
            'user' => $step->approver_ref !== null ? (int) $step->approver_ref : null,
            'manager' => $this->managerUserId($model),
            // department_head resolves the requester's department head, falling
            // back to the reporting line when the department has no head set.
            'department_head' => $this->departmentHeadUserId($model) ?? $this->managerUserId($model),
            default => null, // role-based: resolved dynamically
        };
    }

    /**
     * The user id of the head of the requester employee's current department,
     * if the department has a head whose Employee is linked to a user.
     *
     * @param  Approvable&Model  $model
     */
    private function departmentHeadUserId(Approvable $model): ?int
    {
        $headEmployeeId = $model->approvalRequesterEmployee()?->currentEmployment?->department?->head_employee_id;

        if ($headEmployeeId === null) {
            return null;
        }

        return Employee::find($headEmployeeId)?->user?->id;
    }

    /**
     * The user id of the requester employee's direct manager, if any.
     *
     * @param  Approvable&Model  $model
     */
    private function managerUserId(Approvable $model): ?int
    {
        $manager = $model->approvalRequesterEmployee()?->currentEmployment?->manager;

        return $manager?->user?->id;
    }

    /**
     * Whether the actor is allowed to act on the given step right now.
     */
    private function canActOnStep(ApprovalRequest $request, ApprovalFlowStep $step, User $actor): bool
    {
        // No approver may act twice on the same step (matters for parallel pools).
        if ($this->hasActed($request, $step->order, $actor->id)) {
            return false;
        }

        $state = ApprovalStepState::query()
            ->where('request_id', $request->id)
            ->where('step_order', $step->order)
            ->first();

        // Once a step is escalated past SLA, the escalation target may also act.
        if ($state?->status === 'escalated' && $this->matchesEscalateTarget($actor, $step->escalate_to)) {
            return true;
        }

        if ($step->approver_type === 'role') {
            return $step->approver_ref !== null && $actor->hasRole($step->approver_ref);
        }

        $approverId = $state?->approver_id;

        if ($approverId === null) {
            return false;
        }

        // The resolved approver, or any of their active delegates, may act.
        return (int) $approverId === $actor->id
            || $this->isActiveDelegate($actor->id, (int) $approverId, $request->flow?->transaction_type);
    }

    /**
     * Set the SLA deadline on a step's state when its current step begins, and
     * reset the escalation markers. No deadline when the step has no sla_hours.
     */
    private function armSla(ApprovalRequest $request, ?ApprovalFlowStep $step): void
    {
        if ($step === null) {
            return;
        }

        $state = ApprovalStepState::query()
            ->where('request_id', $request->id)
            ->where('step_order', $step->order)
            ->first();

        if ($state === null) {
            return;
        }

        $state->due_at = $step->sla_hours ? now()->addHours((int) $step->sla_hours) : null;
        $state->reminded_at = null;
        $state->escalated_at = null;
        $state->save();
    }

    /**
     * Notify the approvers responsible for a step that it awaits their action.
     */
    private function notifyAssigned(ApprovalRequest $request, ?ApprovalFlowStep $step): void
    {
        if ($step === null) {
            return;
        }

        $model = $request->approvable;

        $this->notifier->toUsers((int) $request->tenant_id, $this->approverUserIds($request, $step), 'approval.assigned', [
            'request_id' => $request->id,
            'title' => $model instanceof Approvable ? $model->approvalTitle() : null,
        ]);
    }

    /**
     * User ids responsible for acting on a step: the role pool, or the resolved
     * concrete approver.
     *
     * @return list<int>
     */
    public function approverUserIds(ApprovalRequest $request, ApprovalFlowStep $step): array
    {
        if ($step->approver_type === 'role') {
            return $this->usersWithRole((int) $request->tenant_id, $step->approver_ref);
        }

        $approverId = ApprovalStepState::query()
            ->where('request_id', $request->id)
            ->where('step_order', $step->order)
            ->value('approver_id');

        return $approverId !== null ? [(int) $approverId] : [];
    }

    /**
     * User ids the step escalates to (a specific user id or a role pool).
     *
     * @return list<int>
     */
    public function escalateTargetUserIds(ApprovalFlowStep $step, int $tenantId): array
    {
        $ref = $step->escalate_to;

        if ($ref === null || $ref === '') {
            return [];
        }

        return ctype_digit((string) $ref) ? [(int) $ref] : $this->usersWithRole($tenantId, $ref);
    }

    /**
     * Whether the actor matches a step's escalation target (user id or role).
     */
    private function matchesEscalateTarget(User $actor, ?string $ref): bool
    {
        if ($ref === null || $ref === '') {
            return false;
        }

        return ctype_digit($ref) ? (int) $ref === $actor->id : $actor->hasRole($ref);
    }

    /**
     * @return list<int>
     */
    private function usersWithRole(int $tenantId, ?string $role): array
    {
        if ($role === null) {
            return [];
        }

        return User::query()
            ->where('tenant_id', $tenantId)
            ->get()
            ->filter(fn (User $user): bool => $user->hasRole($role))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Whether the actor has already recorded an action on this step.
     */
    private function hasActed(ApprovalRequest $request, int $stepOrder, int $actorId): bool
    {
        return ApprovalAction::query()
            ->where('request_id', $request->id)
            ->where('step_order', $stepOrder)
            ->where('actor_id', $actorId)
            ->exists();
    }

    /**
     * Whether a step has gathered enough approvals to complete. Sequential steps
     * need one; parallel steps need `min_approvals` distinct approvers.
     */
    private function stepSatisfied(ApprovalRequest $request, ApprovalFlowStep $step): bool
    {
        $required = $step->mode === 'parallel' ? max(1, (int) $step->min_approvals) : 1;

        $approvals = ApprovalAction::query()
            ->where('request_id', $request->id)
            ->where('step_order', $step->order)
            ->where('action', ApprovalActionType::Approve->value)
            ->distinct()
            ->count('actor_id');

        return $approvals >= $required;
    }

    /**
     * Update the current step's state row. Role steps record the acting user;
     * concrete-approver steps keep the original approver (delegate aside).
     */
    private function markStep(ApprovalRequest $request, ApprovalFlowStep $step, string $status, User $actor, ?string $reason): void
    {
        $state = ApprovalStepState::query()
            ->where('request_id', $request->id)
            ->where('step_order', $step->order)
            ->first();

        if ($state === null) {
            return;
        }

        if ($state->approver_id === null) {
            $state->approver_id = $actor->id;
        }

        $state->status = $status;
        $state->acted_at = now();
        $state->reason = $reason;
        $state->save();
    }

    /**
     * Build an audit note when the actor is acting as someone else's delegate.
     */
    private function delegationNote(ApprovalRequest $request, ApprovalFlowStep $step, User $actor): ?string
    {
        if ($step->approver_type === 'role') {
            return null;
        }

        $approverId = ApprovalStepState::query()
            ->where('request_id', $request->id)
            ->where('step_order', $step->order)
            ->value('approver_id');

        if ($approverId === null || (int) $approverId === $actor->id) {
            return null;
        }

        return "[Delegasi dari user#{$approverId}]";
    }

    /**
     * Whether the actor holds an active delegation from the given approver that
     * covers this transaction type.
     */
    private function isActiveDelegate(int $actorId, int $fromUserId, ?string $type): bool
    {
        $now = now();

        return ApprovalDelegation::query()
            ->where('to_user_id', $actorId)
            ->where('from_user_id', $fromUserId)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->get()
            ->contains(fn (ApprovalDelegation $delegation): bool => empty($delegation->transaction_types)
                || ($type !== null && in_array($type, (array) $delegation->transaction_types, true)));
    }

    /**
     * Advance to the next step, or finalize as approved when this was the last.
     */
    private function advanceOrComplete(ApprovalRequest $request): void
    {
        $next = $request->flow->steps()
            ->where('order', '>', $request->current_step_order)
            ->orderBy('order')
            ->first();

        if ($next !== null) {
            $request->update(['current_step_order' => $next->order]);
            $this->armSla($request->fresh(), $next);
            $this->notifyAssigned($request, $next);

            return;
        }

        $this->finalize($request, ApprovalStatus::Approved);
    }

    /**
     * Close the request and fire the source-module callback.
     */
    private function finalize(ApprovalRequest $request, ApprovalStatus $status): void
    {
        $request->update([
            'status' => $status,
            'completed_at' => now(),
        ]);

        $model = $request->approvable;

        if ($model instanceof Approvable) {
            match ($status) {
                ApprovalStatus::Approved => $model->onApproved(),
                ApprovalStatus::Rejected => $model->onRejected(),
                default => null, // revision keeps the transaction open
            };
        }

        // Notify the requester of the final outcome.
        if ($request->requested_by !== null) {
            $this->notifier->toUser((int) $request->tenant_id, (int) $request->requested_by, 'approval.'.$status->value, [
                'request_id' => $request->id,
                'title' => $model instanceof Approvable ? $model->approvalTitle() : null,
            ]);
        }
    }

    private function audit(ApprovalRequest $request, User $actor, string $event, ?string $reason): void
    {
        AuditLog::create([
            'tenant_id' => $request->tenant_id,
            'user_id' => $actor->id,
            'auditable_type' => ApprovalRequest::class,
            'auditable_id' => $request->id,
            'event' => $event,
            'old_values' => ['step' => $request->current_step_order],
            'new_values' => ['reason' => $reason],
            'ip' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}
