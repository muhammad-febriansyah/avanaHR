<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $event = fake()->randomElement(['created', 'updated', 'deleted']);

        return [
            'user_id' => User::factory(),
            'auditable_type' => Employee::class,
            'auditable_id' => fake()->numberBetween(1, 50),
            'event' => $event,
            'old_values' => $event === 'created' ? null : ['status' => 'active'],
            'new_values' => $event === 'deleted' ? null : ['status' => 'inactive'],
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function event(string $event): static
    {
        return $this->state(fn (): array => ['event' => $event]);
    }
}
