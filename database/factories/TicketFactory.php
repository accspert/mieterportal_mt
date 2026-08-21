<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph,
            'category' => $this->faker->randomElement(['plumbing', 'electrical', 'heating', 'other']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => 'open',
            'property_id' => Property::factory(),
            'created_by' => 1,
        ];
    }
}
