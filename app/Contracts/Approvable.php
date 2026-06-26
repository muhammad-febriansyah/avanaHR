<?php

namespace App\Contracts;

use App\Models\Employee;

/**
 * A transaction that can be routed through the generic ApprovalEngine.
 *
 * The source module implements this contract and never stores approval logic
 * itself: the engine handles routing, step state, and execution callbacks.
 */
interface Approvable
{
    /**
     * Stable key matching `approval_flows.transaction_type`
     * (e.g. leave|overtime|reimbursement|loan).
     */
    public function approvalType(): string;

    /**
     * Snapshot + routing context persisted on the request and matched against
     * `approval_flows.conditions` (amount, grade, department, etc).
     *
     * @return array<string, mixed>
     */
    public function approvalContext(): array;

    /**
     * Human label shown in the approver inbox.
     */
    public function approvalTitle(): string;

    /**
     * The user who owns the transaction (for SoD / maker-checker). Null when
     * created on behalf by HR with no linked employee user.
     */
    public function approvalRequesterUserId(): ?int;

    /**
     * The employee the transaction is about — used to resolve the reporting
     * line for "manager of requester" approver steps.
     */
    public function approvalRequesterEmployee(): ?Employee;

    /**
     * Executed once the request is fully approved (or auto-approved when no
     * flow is configured).
     */
    public function onApproved(): void;

    /**
     * Executed when any approver rejects the request.
     */
    public function onRejected(): void;
}
