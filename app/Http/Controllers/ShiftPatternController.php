<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftPattern\StoreShiftPatternRequest;
use App\Http\Requests\ShiftPattern\UpdateShiftPatternRequest;
use App\Models\Shift;
use App\Models\ShiftPattern;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShiftPatternController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', ShiftPattern::class);

        $patterns = ShiftPattern::query()
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'config'])
            ->map(fn (ShiftPattern $pattern): array => [
                'id' => $pattern->id,
                'name' => $pattern->name,
                'type' => $pattern->type,
                'days' => $pattern->config['days'] ?? [],
            ]);

        return Inertia::render('shift-patterns/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Pola Shift', 'href' => route('shift-patterns.index')],
            ],
            'patterns' => $patterns,
            'shifts' => $this->shiftOptions(),
        ]);
    }

    public function store(StoreShiftPatternRequest $request): RedirectResponse
    {
        $data = $request->validated();

        ShiftPattern::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'config' => ['days' => $data['days']],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pola shift dibuat.']);

        return back();
    }

    public function update(UpdateShiftPatternRequest $request, ShiftPattern $shiftPattern): RedirectResponse
    {
        $data = $request->validated();

        $shiftPattern->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'config' => ['days' => $data['days']],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pola shift diperbarui.']);

        return back();
    }

    public function destroy(ShiftPattern $shiftPattern): RedirectResponse
    {
        $this->authorize('delete', $shiftPattern);

        $shiftPattern->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pola shift dihapus.']);

        return back();
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function shiftOptions(): array
    {
        return Shift::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (Shift $shift): array => ['id' => $shift->id, 'label' => $shift->code.' — '.$shift->name])
            ->all();
    }
}
