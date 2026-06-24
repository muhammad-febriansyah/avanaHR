<?php

namespace App\Repositories\Organization;

use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeEmployment;
use Illuminate\Support\Collection;

class OrganizationRepository
{
    /**
     * Build the tenant org tree: companies → nested departments,
     * each node carrying a current-headcount total.
     *
     * @return list<array<string, mixed>>
     */
    public function tree(): array
    {
        $companies = Company::query()->orderBy('name')->get(['id', 'name', 'code']);
        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'company_id', 'parent_id', 'name', 'code']);

        $headcounts = EmployeeEmployment::query()
            ->whereNull('end_date')
            ->selectRaw('department_id, COUNT(DISTINCT employee_id) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        return $companies->map(function (Company $company) use ($departments, $headcounts): array {
            $companyDepartments = $departments->where('company_id', $company->id);
            $children = $this->buildDepartmentNodes($companyDepartments, null, $headcounts);

            return [
                'id' => $company->id,
                'type' => 'company',
                'name' => $company->name,
                'code' => $company->code,
                'headcount' => $this->sumHeadcount($children),
                'children' => $children,
            ];
        })->all();
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @param  Collection<int, int>  $headcounts
     * @return list<array<string, mixed>>
     */
    private function buildDepartmentNodes(Collection $departments, ?int $parentId, Collection $headcounts): array
    {
        return $departments
            ->where('parent_id', $parentId)
            ->map(function (Department $department) use ($departments, $headcounts): array {
                $children = $this->buildDepartmentNodes($departments, $department->id, $headcounts);
                $own = (int) ($headcounts[$department->id] ?? 0);

                return [
                    'id' => $department->id,
                    'type' => 'department',
                    'name' => $department->name,
                    'code' => $department->code,
                    'headcount' => $own + $this->sumHeadcount($children),
                    'children' => $children,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    private function sumHeadcount(array $nodes): int
    {
        return array_sum(array_column($nodes, 'headcount'));
    }
}
