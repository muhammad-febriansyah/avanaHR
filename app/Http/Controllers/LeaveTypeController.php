<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveType\StoreLeaveTypeRequest;
use App\Http\Requests\LeaveType\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', LeaveType::class);

        return Inertia::render('leave-types/index', [
            'leaveTypes' => LeaveType::orderBy('code')->get([
                'id', 'code', 'name', 'is_paid', 'max_days_year', 'allow_negative',
            ]),
        ]);
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        LeaveType::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis cuti berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis cuti berhasil diperbarui.']);

        return back();
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('delete', $leaveType);

        $leaveType->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis cuti berhasil dihapus.']);

        return back();
    }
}
