<?php

namespace Database\Factories;

use App\Models\WorkVisit;
use App\Models\WorkVisitReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkVisitReport>
 */
class WorkVisitReportFactory extends Factory
{
    protected $model = WorkVisitReport::class;

    public function definition(): array
    {
        return [
            'work_visit_id' => WorkVisit::factory(),
            'visited_at' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'location' => fake('id_ID')->city(),
            'notes' => fake()->sentence(),
            'attachment_path' => null,
        ];
    }
}
