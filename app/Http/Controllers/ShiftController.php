<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Models\Shift;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', Shift::class);

        $shifts = Shift::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'start_time', 'end_time', 'break_min', 'is_overnight', 'late_tolerance_min', 'grace_min'])
            ->map(fn (Shift $shift): array => [
                'id' => $shift->id,
                'code' => $shift->code,
                'name' => $shift->name,
                'start_time' => substr((string) $shift->start_time, 0, 5),
                'end_time' => substr((string) $shift->end_time, 0, 5),
                'break_min' => $shift->break_min,
                'is_overnight' => (bool) $shift->is_overnight,
                'late_tolerance_min' => $shift->late_tolerance_min,
                'grace_min' => $shift->grace_min,
            ]);

        return Inertia::render('shifts/index', [
            'shifts' => $shifts,
        ]);
    }

    public function store(StoreShiftRequest $request): RedirectResponse
    {
        Shift::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shift berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdateShiftRequest $request, Shift $shift): RedirectResponse
    {
        $shift->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shift berhasil diperbarui.']);

        return back();
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $this->authorize('delete', $shift);

        if ($shift->schedules()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Shift masih dipakai pada jadwal.']);

            return back();
        }

        $shift->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shift berhasil dihapus.']);

        return back();
    }
}
