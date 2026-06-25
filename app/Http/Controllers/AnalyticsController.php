<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeLifecycleEvent;
use App\Models\EmployeeLoan;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\PayrollRun;
use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function workforce(Request $request): Response
    {
        abort_unless($request->user()->can('report.view'), 403);

        $now = Carbon::now();

        $total = Employee::query()->count();
        $active = Employee::query()->where('status', 'active')->count();

        $hiresThisMonth = Employee::query()
            ->whereYear('join_date', $now->year)
            ->whereMonth('join_date', $now->month)
            ->count();

        $separationsThisMonth = EmployeeLifecycleEvent::query()
            ->whereIn('type', ['resign', 'terminate'])
            ->whereYear('effective_date', $now->year)
            ->whereMonth('effective_date', $now->month)
            ->count();

        return Inertia::render('analytics/workforce', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Analitik Workforce', 'href' => route('analytics.workforce')],
            ],
            'kpis' => [
                'total' => $total,
                'active' => $active,
                'hires_this_month' => $hiresThisMonth,
                'separations_this_month' => $separationsThisMonth,
            ],
            'byStatus' => $this->countBy(Employee::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')),
            'byGender' => $this->countBy(Employee::query()->selectRaw('gender, COUNT(*) as total')->groupBy('gender')->pluck('total', 'gender')),
            'byDepartment' => $this->departmentHeadcount(),
            'byType' => $this->countBy(
                EmployeeEmployment::query()
                    ->whereNull('end_date')
                    ->selectRaw('employment_type, COUNT(DISTINCT employee_id) as total')
                    ->groupBy('employment_type')
                    ->pluck('total', 'employment_type'),
            ),
            'hireTrend' => $this->hireTrend($now),
        ]);
    }

    public function executive(Request $request): Response
    {
        abort_unless($request->user()->can('report.view'), 403);

        $now = Carbon::now();
        $headcount = Employee::query()->where('status', 'active')->count();

        $separations12m = EmployeeLifecycleEvent::query()
            ->whereIn('type', ['resign', 'terminate'])
            ->where('effective_date', '>=', $now->copy()->subYear())
            ->count();

        $pending = [
            'leave' => LeaveRequest::query()->where('status', 'pending')->count(),
            'overtime' => OvertimeRequest::query()->where('status', 'pending')->count(),
            'reimbursement' => Reimbursement::query()->where('status', 'pending')->count(),
            'loan' => EmployeeLoan::query()->where('status', 'pending')->count(),
        ];

        $latestRun = PayrollRun::query()->orderByDesc('id')->first();

        return Inertia::render('analytics/executive', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Dashboard Eksekutif', 'href' => route('analytics.executive')],
            ],
            'kpis' => [
                'headcount' => $headcount,
                'turnover_rate' => $headcount > 0 ? round($separations12m / $headcount * 100, 1) : 0,
                'pending_total' => array_sum($pending),
                'payroll_net' => (int) ($latestRun?->net_total ?? 0),
            ],
            'pending' => [
                ['label' => 'Cuti', 'total' => $pending['leave']],
                ['label' => 'Lembur', 'total' => $pending['overtime']],
                ['label' => 'Reimbursement', 'total' => $pending['reimbursement']],
                ['label' => 'Pinjaman', 'total' => $pending['loan']],
            ],
            'payroll' => [
                'run_no' => $latestRun?->run_no,
                'gross' => (int) ($latestRun?->gross_total ?? 0),
                'net' => (int) ($latestRun?->net_total ?? 0),
                'tax' => (int) ($latestRun?->tax_total ?? 0),
                'bpjs' => (int) ($latestRun?->bpjs_total ?? 0),
            ],
            'reimbursementPending' => (int) Reimbursement::query()->where('status', 'pending')->sum('amount'),
            'byDepartment' => $this->departmentHeadcount(),
            'byType' => $this->countBy(
                EmployeeEmployment::query()
                    ->whereNull('end_date')
                    ->selectRaw('employment_type, COUNT(DISTINCT employee_id) as total')
                    ->groupBy('employment_type')
                    ->pluck('total', 'employment_type'),
            ),
        ]);
    }

    /**
     * @param  Collection<string, int>  $counts
     * @return list<array{label: string, total: int}>
     */
    private function countBy(Collection $counts): array
    {
        return $counts
            ->map(fn (int $total, string $label): array => [
                'label' => $label ?: 'Tidak diketahui',
                'total' => $total,
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * Active headcount per department (current employments only).
     *
     * @return list<array{label: string, total: int}>
     */
    private function departmentHeadcount(): array
    {
        return EmployeeEmployment::query()
            ->whereNull('end_date')
            ->with('department:id,name')
            ->get(['id', 'department_id', 'employee_id'])
            ->groupBy(fn (EmployeeEmployment $employment): string => $employment->department?->name ?? 'Tanpa Departemen')
            ->map(fn (Collection $group): int => $group->pluck('employee_id')->unique()->count())
            ->map(fn (int $total, string $label): array => ['label' => $label, 'total' => $total])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * Monthly hire counts for the trailing 6 months (oldest → newest).
     *
     * @return list<array{label: string, total: int}>
     */
    private function hireTrend(Carbon $now): array
    {
        $months = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);

            $months[] = [
                'label' => $month->locale('id')->isoFormat('MMM YY'),
                'total' => Employee::query()
                    ->whereYear('join_date', $month->year)
                    ->whereMonth('join_date', $month->month)
                    ->count(),
            ];
        }

        return $months;
    }
}
