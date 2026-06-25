<?php

namespace Database\Factories;

use App\Models\ReportDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportDefinition>
 */
class ReportDefinitionFactory extends Factory
{
    protected $model = ReportDefinition::class;

    public function definition(): array
    {
        return [
            'name' => 'Laporan '.fake()->words(2, true),
            'source' => 'employees',
            'columns' => ['employee_no', 'first_name', 'status'],
            'filters' => null,
            'grouping' => null,
            'formulas' => null,
            'visibility' => null,
            'created_by' => null,
        ];
    }
}
