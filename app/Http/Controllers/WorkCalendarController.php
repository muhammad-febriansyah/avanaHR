<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkCalendar\StoreWorkCalendarRequest;
use App\Http\Requests\WorkCalendar\UpdateWorkCalendarRequest;
use App\Models\Holiday;
use App\Models\WorkCalendar;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkCalendarController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WorkCalendar::class);

        $calendars = WorkCalendar::query()
            ->withCount('holidays')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default']);

        $selected = $calendars->firstWhere('id', (int) $request->query('calendar'))
            ?? $calendars->first();

        $holidays = $selected
            ? Holiday::query()
                ->where('calendar_id', $selected->id)
                ->orderBy('date')
                ->get(['id', 'calendar_id', 'date', 'name', 'is_national'])
            : collect();

        return Inertia::render('calendars/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Kalender Kerja', 'href' => route('work-calendars.index')],
            ],
            'calendars' => $calendars,
            'holidays' => $holidays,
            'selectedCalendarId' => $selected?->id,
        ]);
    }

    public function store(StoreWorkCalendarRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $calendar = WorkCalendar::create($request->validated());

            if ($calendar->is_default) {
                $this->clearOtherDefaults($calendar);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kalender kerja berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdateWorkCalendarRequest $request, WorkCalendar $workCalendar): RedirectResponse
    {
        DB::transaction(function () use ($request, $workCalendar): void {
            $workCalendar->update($request->validated());

            if ($workCalendar->is_default) {
                $this->clearOtherDefaults($workCalendar);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kalender kerja berhasil diperbarui.']);

        return back();
    }

    public function destroy(WorkCalendar $workCalendar): RedirectResponse
    {
        $this->authorize('delete', $workCalendar);

        if ($workCalendar->is_default) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Kalender default tidak dapat dihapus. Tetapkan kalender lain sebagai default terlebih dahulu.']);

            return back();
        }

        $workCalendar->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kalender kerja berhasil dihapus.']);

        return back();
    }

    public function setDefault(WorkCalendar $workCalendar): RedirectResponse
    {
        $this->authorize('update', $workCalendar);

        DB::transaction(function () use ($workCalendar): void {
            $workCalendar->update(['is_default' => true]);
            $this->clearOtherDefaults($workCalendar);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kalender default berhasil diperbarui.']);

        return back();
    }

    private function clearOtherDefaults(WorkCalendar $calendar): void
    {
        WorkCalendar::query()
            ->whereKeyNot($calendar->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
