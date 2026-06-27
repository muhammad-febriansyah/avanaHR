<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PayslipResource;
use App\Models\Payslip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PayslipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employeeId = $this->employeeId($request);

        $payslips = Payslip::query()
            ->where('employee_id', $employeeId)
            ->with('run.period')
            ->latest('id')
            ->paginate(20);

        return PayslipResource::collection($payslips)->response();
    }

    public function show(Request $request, Payslip $payslip): JsonResponse
    {
        // IDOR guard: a payslip is only ever visible to its own employee.
        if ($payslip->employee_id !== $this->employeeId($request)) {
            throw new AccessDeniedHttpException('Slip gaji ini bukan milik Anda.');
        }

        $payslip->load(['run.period', 'lines']);

        return response()->json(['data' => new PayslipResource($payslip)]);
    }

    private function employeeId(Request $request): int
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw new NotFoundHttpException('Akun ini tidak terhubung ke data karyawan.');
        }

        return $employee->id;
    }
}
