<?php

namespace App\Http\Controllers;

use App\Http\Requests\Position\StorePositionRequest;
use App\Http\Requests\Position\UpdatePositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PositionController extends Controller
{
    use AuthorizesRequests;

    public function store(StorePositionRequest $request): RedirectResponse
    {
        Position::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Posisi berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Posisi berhasil diperbarui.']);

        return back();
    }

    public function destroy(Position $position): RedirectResponse
    {
        $this->authorize('delete', $position);

        $position->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Posisi berhasil dihapus.']);

        return back();
    }
}
