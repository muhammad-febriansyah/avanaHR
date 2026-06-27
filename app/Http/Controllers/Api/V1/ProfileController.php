<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Employee\UpdateEmployeeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
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

    public function update(UpdateProfileRequest $request, UpdateEmployeeAction $action): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw new NotFoundHttpException('Akun ini tidak terhubung ke data karyawan.');
        }

        // Sensitive contact data → maker-checker. Auto-applies when no flow exists,
        // otherwise waits in the approval inbox.
        $result = $action->handle($employee, $request->validated(), $request->user());

        return response()->json([
            'message' => $result['pending']
                ? 'Perubahan data menunggu persetujuan.'
                : 'Data berhasil diperbarui.',
            'pending' => $result['pending'],
        ]);
    }
}
