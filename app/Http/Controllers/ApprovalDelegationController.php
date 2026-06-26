<?php

namespace App\Http\Controllers;

use App\Http\Requests\Approval\StoreApprovalDelegationRequest;
use App\Models\ApprovalDelegation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service approval delegation: a user routes their pending approval tasks
 * to another user for a period (e.g. while on leave). Scoped to the current
 * user — you can only manage delegations you grant.
 */
class ApprovalDelegationController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('approval.act'), 403);

        $user = $request->user();

        $delegations = ApprovalDelegation::query()
            ->where('from_user_id', $user->id)
            ->with('toUser:id,name')
            ->latest('id')
            ->get()
            ->map(fn (ApprovalDelegation $delegation): array => [
                'id' => $delegation->id,
                'to_user' => $delegation->toUser?->name,
                'transaction_types' => $delegation->transaction_types,
                'starts_at' => $delegation->starts_at?->format('Y-m-d'),
                'ends_at' => $delegation->ends_at?->format('Y-m-d'),
                'is_active' => $this->isActive($delegation),
            ]);

        return Inertia::render('approval-delegations/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Delegasi Approval', 'href' => route('approval-delegations.index')],
            ],
            'delegations' => $delegations,
            'users' => User::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $candidate): array => ['id' => $candidate->id, 'label' => $candidate->name]),
            'transactionTypes' => $this->transactionTypes(),
        ]);
    }

    public function store(StoreApprovalDelegationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = $data['transaction_type'] ?? 'all';

        ApprovalDelegation::create([
            'from_user_id' => $request->user()->id,
            'to_user_id' => $data['to_user_id'],
            'transaction_types' => $type === 'all' ? null : [$type],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Delegasi approval dibuat.']);

        return back();
    }

    public function destroy(Request $request, ApprovalDelegation $approvalDelegation): RedirectResponse
    {
        abort_unless($approvalDelegation->from_user_id === $request->user()?->id, 403);

        $approvalDelegation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Delegasi approval dihapus.']);

        return back();
    }

    private function isActive(ApprovalDelegation $delegation): bool
    {
        $now = now();

        return ($delegation->starts_at === null || $delegation->starts_at->lte($now))
            && ($delegation->ends_at === null || $delegation->ends_at->gte($now));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function transactionTypes(): array
    {
        return [
            ['value' => 'all', 'label' => 'Semua Transaksi'],
            ['value' => 'leave', 'label' => 'Cuti'],
            ['value' => 'overtime', 'label' => 'Lembur'],
            ['value' => 'reimbursement', 'label' => 'Reimbursement'],
            ['value' => 'loan', 'label' => 'Pinjaman'],
            ['value' => 'work_visit', 'label' => 'Kunjungan Kerja'],
            ['value' => 'benefit_claim', 'label' => 'Klaim Benefit'],
            ['value' => 'attendance_correction', 'label' => 'Koreksi Absensi'],
        ];
    }
}
