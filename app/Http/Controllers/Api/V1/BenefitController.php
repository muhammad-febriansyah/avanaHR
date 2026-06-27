<?php

namespace App\Http\Controllers\Api\V1;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Controllers\Api\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitBenefitClaimRequest;
use App\Models\BenefitClaim;
use App\Models\EmployeeBenefit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BenefitController extends Controller
{
    use ResolvesEmployee;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): JsonResponse
    {
        $benefits = EmployeeBenefit::query()
            ->where('employee_id', $this->employee($request)->id)
            ->with(['benefitType', 'claims'])
            ->orderByDesc('period_year')
            ->get()
            ->map(fn (EmployeeBenefit $benefit): array => [
                'id' => $benefit->id,
                'benefit_type' => $benefit->benefitType?->name,
                'period_year' => (int) $benefit->period_year,
                'quota' => (int) $benefit->quota,
                'remaining' => $benefit->remainingQuota(),
                'claims' => $benefit->claims->map(fn (BenefitClaim $claim): array => [
                    'id' => $claim->id,
                    'claim_date' => $claim->claim_date?->toDateString(),
                    'amount' => (int) $claim->amount,
                    'description' => $claim->description,
                    'status' => $claim->status instanceof \BackedEnum ? $claim->status->value : $claim->status,
                ]),
            ]);

        return response()->json(['data' => $benefits]);
    }

    public function storeClaim(SubmitBenefitClaimRequest $request, EmployeeBenefit $employeeBenefit): JsonResponse
    {
        // ESS guard: only claim against your own benefit allocation.
        if ($employeeBenefit->employee_id !== $this->employee($request)->id) {
            throw new AccessDeniedHttpException('Plafon benefit ini bukan milik Anda.');
        }

        $claim = $employeeBenefit->claims()->create([
            ...$request->validated(),
            'status' => RequestStatus::Pending,
        ]);

        // Plafond veto lives in BenefitClaim::onApproved() (engine rolls back if exceeded).
        $approval = $this->engine->submit($claim, $request->user());

        return response()->json([
            'message' => $approval === null
                ? 'Klaim benefit disetujui otomatis.'
                : 'Klaim benefit menunggu persetujuan.',
            'pending' => $approval !== null,
            'data' => [
                'id' => $claim->id,
                'amount' => (int) $claim->amount,
                'status' => $claim->status instanceof \BackedEnum ? $claim->status->value : $claim->status,
                'remaining_after_approved' => $employeeBenefit->remainingQuota(),
            ],
        ], 201);
    }
}
