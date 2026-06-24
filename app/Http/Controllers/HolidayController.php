<?php

namespace App\Http\Controllers;

use App\Http\Requests\Holiday\StoreHolidayRequest;
use App\Http\Requests\Holiday\UpdateHolidayRequest;
use App\Models\Holiday;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class HolidayController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        Holiday::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hari libur berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hari libur berhasil diperbarui.']);

        return back();
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->authorize('delete', $holiday);

        $holiday->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hari libur berhasil dihapus.']);

        return back();
    }
}
