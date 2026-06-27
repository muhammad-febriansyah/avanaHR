<?php

namespace App\Http\Controllers\Api\V1;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Controllers\Api\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitOvertimeRequest;
use App\Models\OvertimeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OvertimeController extends Controller
{
    use ResolvesEmployee;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): JsonResponse
    {
        $overtimes = OvertimeRequest::query()
            ->where('employee_id', $this->employee($request)->id)
            ->latest('id')
            ->paginate(20)
            ->through(fn (OvertimeRequest $ot): array => [
                'id' => $ot->id,
                'date' => $ot->date?->toDateString(),
                'planned_minutes' => (int) $ot->planned_minutes,
                'day_type' => $ot->day_type,
                'reason' => $ot->reason,
                'status' => $ot->status instanceof \BackedEnum ? $ot->status->value : $ot->status,
            ]);

        return response()->json($overtimes);
    }

    public function store(SubmitOvertimeRequest $request): JsonResponse
    {
        $employee = $this->employee($request);
        $data = $request->validated();

        $start = Carbon::parse($data['date'].' '.$data['start_time']);
        $end = Carbon::parse($data['date'].' '.$data['end_time']);

        $overtime = OvertimeRequest::create([
            'employee_id' => $employee->id,
            'date' => $data['date'],
            'planned_start' => $start,
            'planned_end' => $end,
            'planned_minutes' => (int) $start->diffInMinutes($end),
            'day_type' => $data['day_type'] ?? 'workday',
            'reason' => $data['reason'] ?? null,
            'status' => RequestStatus::Pending,
        ]);

        $approval = $this->engine->submit($overtime, $request->user());

        return response()->json([
            'message' => $approval === null
                ? 'Pengajuan lembur disetujui otomatis.'
                : 'Pengajuan lembur menunggu persetujuan.',
            'pending' => $approval !== null,
            'data' => [
                'id' => $overtime->id,
                'date' => $overtime->date?->toDateString(),
                'planned_minutes' => (int) $overtime->planned_minutes,
                'day_type' => $overtime->day_type,
                'status' => $overtime->status instanceof \BackedEnum ? $overtime->status->value : $overtime->status,
            ],
        ], 201);
    }
}
