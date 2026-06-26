<?php

namespace App\Http\Controllers;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Requests\ShiftSwap\StoreShiftSwapRequest;
use App\Models\Employee;
use App\Models\ShiftSwap;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShiftSwapController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(): Response
    {
        $this->authorize('viewAny', ShiftSwap::class);

        $swaps = ShiftSwap::query()
            ->with(['requester:id,first_name,last_name,employee_no', 'target:id,first_name,last_name,employee_no'])
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (ShiftSwap $swap): array => [
                'id' => $swap->id,
                'requester' => $swap->requester?->fullName(),
                'target' => $swap->target?->fullName(),
                'date_a' => $swap->date_a?->toDateString(),
                'date_b' => $swap->date_b?->toDateString(),
                'status' => $swap->status->value,
            ]);

        return Inertia::render('shift-swaps/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Tukar Shift', 'href' => route('shift-swaps.index')],
            ],
            'swaps' => $swaps,
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
                ->map(fn (Employee $e): array => ['id' => $e->id, 'label' => $e->fullName().' ('.$e->employee_no.')']),
        ]);
    }

    public function store(StoreShiftSwapRequest $request): RedirectResponse
    {
        $swap = ShiftSwap::create([
            ...$request->validated(),
            'status' => RequestStatus::Pending,
        ]);

        $approval = $this->engine->submit($swap, $request->user());

        $message = $approval === null
            ? 'Tukar shift dibuat dan disetujui otomatis (belum ada alur persetujuan).'
            : 'Tukar shift diajukan dan menunggu persetujuan.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function destroy(ShiftSwap $shiftSwap): RedirectResponse
    {
        $this->authorize('delete', $shiftSwap);

        $shiftSwap->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tukar shift dihapus.']);

        return back();
    }
}
