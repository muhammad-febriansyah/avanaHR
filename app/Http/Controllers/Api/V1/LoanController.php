<?php

namespace App\Http\Controllers\Api\V1;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Controllers\Api\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitLoanRequest;
use App\Models\EmployeeLoan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    use ResolvesEmployee;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): JsonResponse
    {
        $loans = EmployeeLoan::query()
            ->where('employee_id', $this->employee($request)->id)
            ->latest('id')
            ->paginate(20)
            ->through(fn (EmployeeLoan $loan): array => $this->present($loan));

        return response()->json($loans);
    }

    public function store(SubmitLoanRequest $request): JsonResponse
    {
        $data = $request->validated();

        $loan = EmployeeLoan::create([
            'employee_id' => $this->employee($request)->id,
            'principal' => $data['principal'],
            'tenor_months' => $data['tenor_months'],
            'installment' => (int) ceil($data['principal'] / $data['tenor_months']),
            'outstanding' => $data['principal'],
            'status' => RequestStatus::Pending,
        ]);

        $approval = $this->engine->submit($loan, $request->user());

        return response()->json([
            'message' => $approval === null
                ? 'Pinjaman disetujui otomatis.'
                : 'Pinjaman menunggu persetujuan.',
            'pending' => $approval !== null,
            'data' => $this->present($loan),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(EmployeeLoan $loan): array
    {
        return [
            'id' => $loan->id,
            'principal' => (int) $loan->principal,
            'tenor_months' => (int) $loan->tenor_months,
            'installment' => (int) $loan->installment,
            'outstanding' => (int) $loan->outstanding,
            'status' => $loan->status instanceof \BackedEnum ? $loan->status->value : $loan->status,
            'created_at' => $loan->created_at?->toIso8601String(),
        ];
    }
}
