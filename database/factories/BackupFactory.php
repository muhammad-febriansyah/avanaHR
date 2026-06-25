<?php

namespace Database\Factories;

use App\Models\Backup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Backup>
 */
class BackupFactory extends Factory
{
    protected $model = Backup::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'type' => fake()->randomElement(['full', 'database', 'files']),
            'status' => 'completed',
            'location' => 'backups/platform-'.fake()->numerify('########-######').'.zip',
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => ['status' => 'failed', 'location' => null]);
    }
}
