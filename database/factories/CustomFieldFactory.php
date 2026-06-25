<?php

namespace Database\Factories;

use App\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomField>
 */
class CustomFieldFactory extends Factory
{
    protected $model = CustomField::class;

    public function definition(): array
    {
        return [
            'entity_type' => 'employee',
            'key' => 'field_'.fake()->unique()->numberBetween(1, 9999),
            'label' => fake()->words(2, true),
            'type' => 'text',
            'options' => null,
            'is_required' => false,
            'order' => 0,
        ];
    }

    public function select(): static
    {
        return $this->state(fn (): array => [
            'type' => 'select',
            'options' => ['S', 'M', 'L', 'XL'],
        ]);
    }
}
