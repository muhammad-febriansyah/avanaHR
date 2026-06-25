<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\HrTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrTicket>
 */
class HrTicketFactory extends Factory
{
    protected $model = HrTicket::class;

    public function definition(): array
    {
        return [
            'ticket_no' => 'TKT-'.fake()->unique()->numerify('######'),
            'employee_id' => Employee::factory(),
            'category' => fake()->randomElement(['leave', 'payroll', 'attendance', 'data', 'general']),
            'subject' => fake()->sentence(4),
            'status' => 'open',
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'sla_due_at' => now()->addDay(),
            'resolved_at' => null,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
