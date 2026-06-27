<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw new NotFoundHttpException('Akun ini tidak terhubung ke data karyawan.');
        }

        $employee->loadMissing([
            'currentEmployment.company',
            'currentEmployment.branch',
            'currentEmployment.department',
            'currentEmployment.position',
            'currentEmployment.jobGrade',
        ]);

        return response()->json(['data' => new ProfileResource($employee)]);
    }
}
