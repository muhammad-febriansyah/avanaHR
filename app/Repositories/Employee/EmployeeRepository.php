<?php

namespace App\Repositories\Employee;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmployeeRepository
{
    private const SORTABLE = ['employee_no', 'first_name', 'status', 'join_date', 'created_at'];

    /**
     * Paginated, eager-loaded list for the index DataTable.
     *
     * @param  array<string, mixed>  $params
     */
    public function paginateForIndex(array $params): LengthAwarePaginator
    {
        $sort = in_array($params['sort'] ?? '', self::SORTABLE, true) ? $params['sort'] : 'created_at';
        $dir = ($params['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) ($params['per_page'] ?? 15), 100);

        return Employee::query()
            ->with([
                'currentEmployment.position:id,name',
                'currentEmployment.department:id,name',
            ])
            ->when($params['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_no', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($params['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Employee count per status for the current tenant.
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        return Employee::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    public function loadDetail(Employee $employee): Employee
    {
        return $employee->load([
            'currentEmployment.position:id,name',
            'currentEmployment.department:id,name',
            'currentEmployment.branch:id,name',
            'currentEmployment.jobGrade:id,name',
            'bankAccounts',
            'dependents',
        ]);
    }
}
