<?php

namespace App\Actions\Schedule;

use App\Models\Schedule;
use App\Models\ShiftPattern;
use App\Support\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Generates roster (schedule) rows for employees over a date range from a
 * cyclic shift pattern. The pattern's config.days is a repeating sequence of
 * shift ids (null = day off, skipped). Idempotent per (employee, date): an
 * existing entry is overwritten so re-generating a range is safe.
 */
class GenerateScheduleAction
{
    public function __construct(private readonly CurrentTenant $tenant) {}

    /**
     * @param  array<int, int>  $employeeIds
     * @return int number of schedule rows written
     */
    public function execute(ShiftPattern $pattern, array $employeeIds, CarbonImmutable $start, CarbonImmutable $end): int
    {
        /** @var list<int|null> $days */
        $days = $pattern->config['days'] ?? [];
        $cycle = count($days);

        if ($cycle === 0 || $employeeIds === [] || $end->lt($start)) {
            return 0;
        }

        $tenantId = $this->tenant->id();
        $written = 0;

        DB::transaction(function () use ($days, $cycle, $employeeIds, $start, $end, $tenantId, &$written): void {
            foreach ($employeeIds as $employeeId) {
                for ($date = $start; $date->lte($end); $date = $date->addDay()) {
                    $offset = $start->diffInDays($date) % $cycle;
                    $shiftId = $days[$offset] ?? null;

                    if ($shiftId === null) {
                        continue; // day off
                    }

                    Schedule::updateOrCreate(
                        ['tenant_id' => $tenantId, 'employee_id' => $employeeId, 'date' => $date->toDateString()],
                        ['shift_id' => $shiftId, 'status' => 'planned'],
                    );
                    $written++;
                }
            }
        });

        return $written;
    }
}
