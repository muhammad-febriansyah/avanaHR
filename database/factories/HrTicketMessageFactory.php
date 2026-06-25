<?php

namespace Database\Factories;

use App\Models\HrTicket;
use App\Models\HrTicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrTicketMessage>
 */
class HrTicketMessageFactory extends Factory
{
    protected $model = HrTicketMessage::class;

    public function definition(): array
    {
        return [
            'ticket_id' => HrTicket::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
