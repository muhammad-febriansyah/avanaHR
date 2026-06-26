<?php

namespace App\Http\Controllers;

use App\Approvals\ApprovalEngine;
use App\Approvals\ApprovalException;
use App\Contracts\Approvable;
use App\Enums\ApprovalActionType;
use App\Enums\ApprovalStatus;
use App\Http\Requests\Approval\ActOnApprovalRequest;
use App\Models\ApprovalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Approver inbox: the web surface where managers / HR / finance act on the
 * approval requests routed to them by the {@see ApprovalEngine}.
 */
class ApprovalController extends Controller
{
    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('approval.act'), 403);

        $user = $request->user();

        $pending = ApprovalRequest::query()
            ->where('status', ApprovalStatus::Pending)
            ->with(['flow.steps', 'stepStates', 'approvable', 'requestedBy:id,name'])
            ->latest('id')
            ->get()
            ->filter(fn (ApprovalRequest $approval): bool => $this->engine->canAct($approval, $user))
            ->values()
            ->map(fn (ApprovalRequest $approval): array => $this->summarize($approval));

        return Inertia::render('approvals/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Inbox Approval', 'href' => route('approvals.index')],
            ],
            'requests' => $pending,
        ]);
    }

    public function show(Request $request, ApprovalRequest $approvalRequest): Response
    {
        abort_unless($request->user()?->can('approval.act'), 403);

        $approvalRequest->load([
            'flow.steps' => fn ($query) => $query->orderBy('order'),
            'stepStates',
            'actions.actor:id,name',
            'requestedBy:id,name',
            'approvable',
        ]);

        $states = $approvalRequest->stepStates->keyBy('step_order');

        $steps = $approvalRequest->flow?->steps->map(fn ($step): array => [
            'order' => $step->order,
            'approver_type' => $step->approver_type,
            'approver_ref' => $step->approver_ref,
            'mode' => $step->mode,
            'status' => $states->get($step->order)?->status ?? 'pending',
            'reason' => $states->get($step->order)?->reason,
            'acted_at' => $states->get($step->order)?->acted_at?->format('Y-m-d H:i'),
            'is_current' => $step->order === $approvalRequest->current_step_order
                && $approvalRequest->status === ApprovalStatus::Pending,
        ]) ?? collect();

        return Inertia::render('approvals/show', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Inbox Approval', 'href' => route('approvals.index')],
                ['title' => '#'.$approvalRequest->id, 'href' => route('approvals.show', $approvalRequest)],
            ],
            'request' => [
                ...$this->summarize($approvalRequest),
                'flow_name' => $approvalRequest->flow?->name,
                'steps' => $steps,
                'history' => $approvalRequest->actions
                    ->sortBy('created_at')
                    ->values()
                    ->map(fn ($action): array => [
                        'actor' => $action->actor?->name,
                        'action' => $action->action,
                        'reason' => $action->reason,
                        'at' => $action->created_at?->format('Y-m-d H:i'),
                    ]),
            ],
            'canAct' => $this->engine->canAct($approvalRequest, $request->user()),
        ]);
    }

    public function act(ActOnApprovalRequest $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        try {
            $this->engine->act(
                $approvalRequest,
                $request->user(),
                ApprovalActionType::from($request->validated('action')),
                $request->validated('reason'),
            );
        } catch (ApprovalException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Keputusan persetujuan tersimpan.']);

        return to_route('approvals.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function summarize(ApprovalRequest $approval): array
    {
        $approvable = $approval->approvable;

        return [
            'id' => $approval->id,
            'type' => $approval->flow?->transaction_type ?? ($approvable instanceof Approvable ? $approvable->approvalType() : '—'),
            'title' => $approvable instanceof Approvable ? $approvable->approvalTitle() : 'Transaksi #'.$approval->approvable_id,
            'requester' => $approval->requestedBy?->name,
            'status' => $approval->status->value,
            'current_step' => $approval->current_step_order,
            'total_steps' => $approval->flow?->steps->count() ?? 0,
            'submitted_at' => $approval->submitted_at?->format('Y-m-d H:i'),
        ];
    }
}
