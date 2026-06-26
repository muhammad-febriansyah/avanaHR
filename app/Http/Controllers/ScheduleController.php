<?php

namespace App\Http\Controllers;

use App\Actions\Schedule\GenerateScheduleAction;
use App\Http\Requests\Schedule\GenerateScheduleRequest;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftPattern;
use App\Support\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Schedule::class);

        $filters = $request->only('employee_id', 'shift_id', 'date_from', 'date_to');

        $schedules = Schedule::query()
            ->with(['employee:id,first_name,last_name,employee_no', 'shift:id,code,name'])
            ->when($filters['employee_id'] ?? null, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($filters['shift_id'] ?? null, fn ($q, $id) => $q->where('shift_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('date', '<=', $date))
            ->orderBy('date')
            ->orderBy('employee_id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Schedule $schedule): array => [
                'id' => $schedule->id,
                'employee_name' => $schedule->employee?->fullName(),
                'employee_no' => $schedule->employee?->employee_no,
                'date' => $schedule->date?->toDateString(),
                'shift_code' => $schedule->shift?->code,
                'shift_name' => $schedule->shift?->name,
                'status' => $schedule->status,
            ]);

        return Inertia::render('schedules/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Roster Shift', 'href' => route('schedules.index')],
            ],
            'schedules' => $schedules,
            'filters' => (object) $filters,
            'options' => [
                'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
                    ->map(fn (Employee $e): array => ['id' => $e->id, 'label' => $e->fullName().' ('.$e->employee_no.')']),
                'shifts' => Shift::orderBy('code')->get(['id', 'code', 'name'])
                    ->map(fn (Shift $s): array => ['id' => $s->id, 'label' => $s->code.' — '.$s->name]),
                'patterns' => ShiftPattern::orderBy('name')->get(['id', 'name'])
                    ->map(fn (ShiftPattern $p): array => ['id' => $p->id, 'label' => $p->name]),
            ],
        ]);
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Schedule::updateOrCreate(
            [
                'tenant_id' => app(CurrentTenant::class)->id(),
                'employee_id' => $data['employee_id'],
                'date' => CarbonImmutable::parse($data['date'])->toDateString(),
            ],
            ['shift_id' => $data['shift_id'], 'status' => 'planned'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal disimpan.']);

        return back();
    }

    public function generate(GenerateScheduleRequest $request, GenerateScheduleAction $action): RedirectResponse
    {
        $data = $request->validated();
        $pattern = ShiftPattern::findOrFail($data['pattern_id']);

        $written = $action->execute(
            $pattern,
            $data['employee_ids'],
            CarbonImmutable::parse($data['start_date']),
            CarbonImmutable::parse($data['end_date']),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Roster dibuat: {$written} jadwal."]);

        return back();
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $this->authorize('delete', $schedule);

        $schedule->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal dihapus.']);

        return back();
    }
}
