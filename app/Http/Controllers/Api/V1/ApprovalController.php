<?php

namespace App\Http\Controllers\Api\V1;

use App\Approvals\ApprovalEngine;
use App\Approvals\ApprovalException;
use App\Contracts\Approvable;
use App\Enums\ApprovalActionType;
use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActOnApprovalRequest;
use App\Http\Requests\Api\BulkActOnApprovalRequest;
use App\Models\ApprovalRequest;
use App\Models\AttendanceCorrection;
use App\Models\BenefitClaim;
use App\Models\EmployeeChangeRequest;
use App\Models\EmployeeLoan;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Reimbursement;
use App\Models\ShiftSwap;
use App\Models\WorkVisit;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MSS approval inbox for the mobile app: list pending requests the manager may
 * act on, decide a single request, or bulk-approve/reject many at once.
 */
class ApprovalController extends Controller
{
    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('approval.act', 'web'), 403);

        $user = $request->user();

        $pending = ApprovalRequest::query()
            ->where('status', ApprovalStatus::Pending)
            ->with(['flow.steps', 'stepStates', 'approvable' => $this->approvableEager(), 'requestedBy:id,name'])
            ->latest('id')
            ->get()
            ->filter(fn (ApprovalRequest $approval): bool => $this->engine->canAct($approval, $user))
            ->values()
            ->map(fn (ApprovalRequest $approval): array => $this->summarize($approval));

        return response()->json(['data' => $pending]);
    }

    public function act(ActOnApprovalRequest $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('approval.act', 'web'), 403);

        try {
            $this->engine->act(
                $approvalRequest,
                $request->user(),
                ApprovalActionType::from($request->validated('action')),
                $request->validated('reason'),
            );
        } catch (ApprovalException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Keputusan tersimpan.']);
    }

    public function bulk(BulkActOnApprovalRequest $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('approval.act', 'web'), 403);

        $action = ApprovalActionType::from($request->validated('action'));
        $reason = $request->validated('reason');
        $user = $request->user();

        $results = [];

        foreach ($request->validated('ids') as $id) {
            $approval = ApprovalRequest::query()->find($id);

            if (! $approval || ! $this->engine->canAct($approval, $user)) {
                $results[] = ['id' => (int) $id, 'ok' => false, 'message' => 'Tidak dapat diproses (bukan approver / sudah selesai).'];

                continue;
            }

            try {
                $this->engine->act($approval, $user, $action, $reason);
                $results[] = ['id' => (int) $id, 'ok' => true, 'message' => 'Diproses.'];
            } catch (ApprovalException $exception) {
                $results[] = ['id' => (int) $id, 'ok' => false, 'message' => $exception->getMessage()];
            }
        }

        $ok = count(array_filter($results, fn (array $r): bool => $r['ok']));

        return response()->json([
            'message' => "$ok dari ".count($results).' pengajuan diproses.',
            'processed' => $ok,
            'results' => $results,
        ]);
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
            'submitted_at' => $approval->submitted_at?->toIso8601String(),
        ];
    }

    /**
     * Constrained eager load for the polymorphic approvable so title/type
     * rendering never triggers a lazy load.
     */
    private function approvableEager(): \Closure
    {
        return fn (MorphTo $morphTo) => $morphTo->morphWith([
            LeaveRequest::class => ['employee'],
            OvertimeRequest::class => ['employee'],
            WorkVisit::class => ['employee'],
            Reimbursement::class => ['employee'],
            EmployeeLoan::class => ['employee'],
            EmployeeChangeRequest::class => ['employee'],
            ShiftSwap::class => ['requester', 'target'],
            AttendanceCorrection::class => ['employee'],
            BenefitClaim::class => ['employeeBenefit.employee', 'employeeBenefit.benefitType'],
        ]);
    }
}
