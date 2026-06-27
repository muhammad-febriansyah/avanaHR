<?php

namespace App\Http\Controllers\Api\V1;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Controllers\Api\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitReimbursementRequest;
use App\Models\Reimbursement;
use Illuminate\Http\JsonResponse;

class ReimbursementController extends Controller
{
    use ResolvesEmployee;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function store(SubmitReimbursementRequest $request): JsonResponse
    {
        $employee = $this->employee($request);
        $data = $request->validated();

        $reimbursement = Reimbursement::create([
            'employee_id' => $employee->id,
            'category' => $data['category'],
            'amount' => $data['amount'],
            'settlement' => $data['settlement'] ?? 'payroll',
            'status' => RequestStatus::Pending,
        ]);

        $approval = $this->engine->submit($reimbursement, $request->user());

        return response()->json([
            'message' => $approval === null
                ? 'Reimbursement disetujui otomatis.'
                : 'Reimbursement menunggu persetujuan.',
            'pending' => $approval !== null,
            'data' => [
                'id' => $reimbursement->id,
                'category' => $reimbursement->category,
                'amount' => (int) $reimbursement->amount,
                'settlement' => $reimbursement->settlement,
                'status' => $reimbursement->status instanceof \BackedEnum ? $reimbursement->status->value : $reimbursement->status,
            ],
        ], 201);
    }
}
