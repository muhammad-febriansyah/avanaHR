<?php

namespace App\Http\Controllers;

use App\Http\Requests\ThrBonusRun\StoreThrBonusRunRequest;
use App\Http\Requests\ThrBonusRun\UpdateThrBonusRunRequest;
use App\Models\ThrBonusRun;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ThrBonusRunController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ThrBonusRun::class);

        $filters = $request->only('type', 'status');

        $runs = ThrBonusRun::query()
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->get(['id', 'type', 'period_ref', 'status'])
            ->map(fn (ThrBonusRun $run): array => [
                'id' => $run->id,
                'type' => $run->type,
                'period_ref' => $run->period_ref,
                'status' => $run->status,
            ]);

        return Inertia::render('thr-bonus-runs/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'THR & Bonus', 'href' => route('thr-bonus-runs.index')],
            ],
            'runs' => $runs,
            'filters' => (object) $filters,
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(StoreThrBonusRunRequest $request): RedirectResponse
    {
        ThrBonusRun::create([
            ...$request->validated(),
            'status' => 'draft',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Run THR/Bonus berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdateThrBonusRunRequest $request, ThrBonusRun $thrBonusRun): RedirectResponse
    {
        $thrBonusRun->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Run THR/Bonus berhasil diperbarui.']);

        return back();
    }

    public function destroy(ThrBonusRun $thrBonusRun): RedirectResponse
    {
        $this->authorize('delete', $thrBonusRun);

        if ($thrBonusRun->status !== 'draft') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya run berstatus draft yang dapat dihapus.']);

            return back();
        }

        $thrBonusRun->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Run THR/Bonus berhasil dihapus.']);

        return back();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return [
            ['value' => 'thr', 'label' => 'THR'],
            ['value' => 'bonus', 'label' => 'Bonus'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'calculated', 'label' => 'Calculated'],
            ['value' => 'approved', 'label' => 'Approved'],
            ['value' => 'disbursed', 'label' => 'Disbursed'],
        ];
    }
}
