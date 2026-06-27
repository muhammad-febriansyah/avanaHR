<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LeaveBalanceResource;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LeaveController extends Controller
{
    public function balances(Request $request): JsonResponse
    {
        $balances = LeaveBalance::query()
            ->where('employee_id', $this->employeeId($request))
            ->with('leaveType')
            ->orderByDesc('year')
            ->get();

        return response()->json([
            'data' => LeaveBalanceResource::collection($balances),
        ]);
    }

    public function requests(Request $request): JsonResponse
    {
        $requests = LeaveRequest::query()
            ->where('employee_id', $this->employeeId($request))
            ->with('leaveType')
            ->latest('id')
            ->paginate(20);

        return LeaveRequestResource::collection($requests)->response();
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
