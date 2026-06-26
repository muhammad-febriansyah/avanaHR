<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovalFlow\StoreApprovalFlowRequest;
use App\Http\Requests\ApprovalFlow\StoreApprovalFlowStepRequest;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\Branch;
use App\Models\JobGrade;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalFlowController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', ApprovalFlow::class);

        $flows = ApprovalFlow::query()
            ->withCount('steps')
            ->orderByDesc('id')
            ->paginate(15)
            ->through(fn (ApprovalFlow $flow): array => [
                'id' => $flow->id,
                'name' => $flow->name,
                'transaction_type' => $flow->transaction_type,
                'is_active' => $flow->is_active,
                'steps_count' => $flow->steps_count,
            ]);

        return Inertia::render('approval-flows/index', [
            'breadcrumbs' => $this->crumbs(),
            'flows' => $flows,
            'transactionTypes' => $this->transactionTypes(),
        ]);
    }

    public function store(StoreApprovalFlowRequest $request): RedirectResponse
    {
        $flow = ApprovalFlow::create([
            'name' => $request->validated('name'),
            'transaction_type' => $request->validated('transaction_type'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Alur persetujuan dibuat.']);

        return to_route('approval-flows.show', $flow);
    }

    public function show(ApprovalFlow $approvalFlow): Response
    {
        $this->authorize('view', $approvalFlow);

        $approvalFlow->load(['steps' => fn ($query) => $query->orderBy('order')]);

        return Inertia::render('approval-flows/show', [
            'breadcrumbs' => [...$this->crumbs(), ['title' => $approvalFlow->name, 'href' => route('approval-flows.show', $approvalFlow)]],
            'flow' => [
                'id' => $approvalFlow->id,
                'name' => $approvalFlow->name,
                'transaction_type' => $approvalFlow->transaction_type,
                'is_active' => $approvalFlow->is_active,
                'conditions' => $approvalFlow->conditions ?? [],
                'steps' => $approvalFlow->steps->map(fn (ApprovalFlowStep $step): array => [
                    'id' => $step->id,
                    'order' => $step->order,
                    'approver_type' => $step->approver_type,
                    'approver_ref' => $step->approver_ref,
                    'mode' => $step->mode,
                    'min_approvals' => $step->min_approvals,
                    'sla_hours' => $step->sla_hours,
                    'allow_delegate' => $step->allow_delegate,
                ]),
            ],
            'transactionTypes' => $this->transactionTypes(),
            'approverTypes' => $this->approverTypes(),
            'gradeOptions' => JobGrade::query()
                ->orderBy('code')
                ->get(['code', 'name'])
                ->map(fn (JobGrade $grade): array => ['value' => $grade->code, 'label' => "{$grade->code} - {$grade->name}"])
                ->all(),
            'branchOptions' => Branch::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Branch $branch): array => ['value' => (string) $branch->id, 'label' => $branch->name])
                ->all(),
        ]);
    }

    public function update(ApprovalFlow $approvalFlow): RedirectResponse
    {
        $this->authorize('update', $approvalFlow);

        $approvalFlow->update(['is_active' => ! $approvalFlow->is_active]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status alur diperbarui.']);

        return back();
    }

    public function updateConditions(Request $request, ApprovalFlow $approvalFlow): RedirectResponse
    {
        $this->authorize('update', $approvalFlow);

        $validated = $request->validate([
            'amount_min' => ['nullable', 'integer', 'min:0'],
            'amount_max' => ['nullable', 'integer', 'min:0'],
            'grade_in' => ['nullable', 'array'],
            'grade_in.*' => ['string'],
            'department_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        // Build the conditions, omitting null/empty values so an unset field
        // never over-constrains routing (empty conditions = always match).
        $conditions = [];

        foreach (['amount_min', 'amount_max', 'department_id', 'branch_id'] as $key) {
            if (($validated[$key] ?? null) !== null) {
                $conditions[$key] = $validated[$key];
            }
        }

        $gradeIn = array_values(array_filter(
            $validated['grade_in'] ?? [],
            static fn (string $code): bool => trim($code) !== '',
        ));

        if ($gradeIn !== []) {
            $conditions['grade_in'] = $gradeIn;
        }

        $approvalFlow->update(['conditions' => $conditions ?: null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kondisi routing diperbarui.']);

        return back();
    }

    public function destroy(ApprovalFlow $approvalFlow): RedirectResponse
    {
        $this->authorize('delete', $approvalFlow);

        $approvalFlow->steps()->delete();
        $approvalFlow->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Alur persetujuan dihapus.']);

        return to_route('approval-flows.index');
    }

    public function storeStep(StoreApprovalFlowStepRequest $request, ApprovalFlow $approvalFlow): RedirectResponse
    {
        $this->authorize('update', $approvalFlow);

        $nextOrder = (int) $approvalFlow->steps()->max('order') + 1;

        $approvalFlow->steps()->create([
            'order' => $nextOrder,
            'approver_type' => $request->validated('approver_type'),
            'approver_ref' => $request->validated('approver_ref'),
            'mode' => $request->validated('mode'),
            'min_approvals' => $request->validated('min_approvals'),
            'sla_hours' => $request->validated('sla_hours'),
            'allow_delegate' => $request->boolean('allow_delegate'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Langkah persetujuan ditambahkan.']);

        return back();
    }

    public function destroyStep(ApprovalFlowStep $step): RedirectResponse
    {
        // Step is not tenant-scoped directly; ensure its flow belongs to the tenant.
        abort_unless($step->flow !== null, 404);
        $this->authorize('update', $step->flow);

        $step->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Langkah persetujuan dihapus.']);

        return back();
    }

    /**
     * @return list<array{title: string, href: string}>
     */
    private function crumbs(): array
    {
        return [
            ['title' => 'Dashboard', 'href' => route('dashboard')],
            ['title' => 'Workflow Approval', 'href' => route('approval-flows.index')],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function transactionTypes(): array
    {
        return [
            ['value' => 'leave', 'label' => 'Cuti'],
            ['value' => 'overtime', 'label' => 'Lembur'],
            ['value' => 'reimbursement', 'label' => 'Reimbursement'],
            ['value' => 'loan', 'label' => 'Pinjaman'],
            ['value' => 'work_visit', 'label' => 'Kunjungan Kerja'],
            ['value' => 'benefit_claim', 'label' => 'Klaim Benefit'],
            ['value' => 'attendance_correction', 'label' => 'Koreksi Absensi'],
            ['value' => 'lifecycle', 'label' => 'Perubahan Data'],
            ['value' => 'payroll', 'label' => 'Payroll'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function approverTypes(): array
    {
        return [
            ['value' => 'manager', 'label' => 'Atasan Langsung'],
            ['value' => 'department_head', 'label' => 'Kepala Departemen'],
            ['value' => 'role', 'label' => 'Peran (Role)'],
            ['value' => 'user', 'label' => 'Pengguna Tertentu'],
        ];
    }
}
