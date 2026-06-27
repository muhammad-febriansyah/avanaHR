<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Employee;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ResolvesEmployee
{
    /**
     * The employee record linked to the authenticated mobile user.
     */
    protected function employee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw new NotFoundHttpException('Akun ini tidak terhubung ke data karyawan.');
        }

        return $employee;
    }
}
