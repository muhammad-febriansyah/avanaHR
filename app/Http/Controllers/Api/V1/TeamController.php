<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MSS team view: the direct reports of the authenticated manager, resolved from
 * the current employment reporting line (manager_id).
 */
class TeamController extends Controller
{
    use ResolvesEmployee;

    public function index(Request $request): JsonResponse
    {
        $managerEmployeeId = $this->employee($request)->id;

        $team = Employee::query()
            ->whereHas('currentEmployment', fn ($query) => $query->where('manager_id', $managerEmployeeId))
            ->with(['currentEmployment.department', 'currentEmployment.position'])
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'full_name' => $employee->fullName(),
                'status' => $employee->status instanceof \BackedEnum ? $employee->status->value : $employee->status,
                'department' => $employee->currentEmployment?->department?->name,
                'position' => $employee->currentEmployment?->position?->name,
                'photo_url' => $employee->photo_path ? asset('storage/'.$employee->photo_path) : null,
            ]);

        return response()->json(['data' => $team]);
    }
}
