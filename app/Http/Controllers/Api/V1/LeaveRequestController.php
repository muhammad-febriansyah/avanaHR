<?php

namespace App\Http\Controllers\Api\V1;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Controllers\Api\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitLeaveRequest;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class LeaveRequestController extends Controller
{
    use ResolvesEmployee;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function store(SubmitLeaveRequest $request): JsonResponse
    {
        $employee = $this->employee($request);
        $data = $request->validated();

        $days = (float) (Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1);

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => RequestStatus::Pending,
        ]);

        $approval = $this->engine->submit($leave, $request->user());

        return response()->json([
            'message' => $approval === null
                ? 'Pengajuan cuti disetujui otomatis.'
                : 'Pengajuan cuti menunggu persetujuan.',
            'pending' => $approval !== null,
            'data' => new LeaveRequestResource($leave->load('leaveType')),
        ], 201);
    }
}
