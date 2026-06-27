<?php

namespace App\Http\Controllers\Api\V1;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Controllers\Api\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitWorkVisitRequest;
use App\Models\WorkVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkVisitController extends Controller
{
    use ResolvesEmployee;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): JsonResponse
    {
        $visits = WorkVisit::query()
            ->where('employee_id', $this->employee($request)->id)
            ->latest('id')
            ->paginate(20)
            ->through(fn (WorkVisit $visit): array => $this->present($visit));

        return response()->json($visits);
    }

    public function store(SubmitWorkVisitRequest $request): JsonResponse
    {
        $visit = WorkVisit::create([
            'employee_id' => $this->employee($request)->id,
            ...$request->validated(),
            'status' => RequestStatus::Pending,
        ]);

        $approval = $this->engine->submit($visit, $request->user());

        return response()->json([
            'message' => $approval === null
                ? 'Kunjungan kerja disetujui otomatis.'
                : 'Kunjungan kerja menunggu persetujuan.',
            'pending' => $approval !== null,
            'data' => $this->present($visit),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(WorkVisit $visit): array
    {
        return [
            'id' => $visit->id,
            'destination' => $visit->destination,
            'purpose' => $visit->purpose,
            'start_date' => $visit->start_date?->toDateString(),
            'end_date' => $visit->end_date?->toDateString(),
            'transport_mode' => $visit->transport_mode,
            'estimated_cost' => $visit->estimated_cost !== null ? (int) $visit->estimated_cost : null,
            'status' => $visit->status instanceof \BackedEnum ? $visit->status->value : $visit->status,
            'created_at' => $visit->created_at?->toIso8601String(),
        ];
    }
}
